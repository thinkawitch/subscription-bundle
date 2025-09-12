<?php

namespace Thinkawitch\SubscriptionBundle\Subscription;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Thinkawitch\SubscriptionBundle\Event\SubscriptionEvent;
use Thinkawitch\SubscriptionBundle\Event\SubscriptionEvents;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductFinalNotValidException;
use Thinkawitch\SubscriptionBundle\Exception\StrategyNotFoundException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\PermanentSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionIntegrityException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionRenewalException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionStatusException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\Reason;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Registry\SubscriptionRegistry;
use Thinkawitch\SubscriptionBundle\Repository\SubscriptionRepositoryInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Subscription\SubscriptionStrategyInterface;

/**
 * Manages subscription workflow.
 *
 */
class SubscriptionManager
{
    public function __construct(
        private readonly SubscriptionRegistry $registry,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly array $config,
    )
    {
    }

    /**
     * Create a new subscription with a determinate strategy.
     *
     * @param ProductInterface  $product      Product that you want associate with subscription
     * @param UserInterface     $user         User to associate to subscription
     * @param ?string           $strategyName If you keep this null it will use product default strategy
     * @param ?ProductInterface $prolongProduct to set start date from the other product subscription
     *
     * @return SubscriptionInterface
     *
     * @throws StrategyNotFoundException
     * @throws SubscriptionIntegrityException
     * @throws PermanentSubscriptionException
     */
    public function create(
        ProductInterface $product,
        UserInterface $user,
        ?string $strategyName = null,
        ?ProductInterface $prolongProduct = null, // to set start date from the other product subscription
    ): SubscriptionInterface
    {
        // get strategy
        $strategyName = $strategyName ?? $product->getStrategy();
        $strategy     = $this->registry->get($strategyName);

        // get current enabled subscriptions of product
        $subscriptions = $this->subscriptionRepository->findSubscriptionsByProduct($product, $user);

        // check that subscriptions collection are a valid objects
        foreach ($subscriptions as $activeSubscription) {
            $this->checkSubscriptionIntegrity($activeSubscription);
        }

        // when product is changed - prolongs date range of original product subscription.
        if ($prolongProduct) {
            $prolongProductSubscriptions = $this->subscriptionRepository->findSubscriptionsByProduct($prolongProduct, $user);
            foreach ($prolongProductSubscriptions as $pps) {
                $this->checkSubscriptionIntegrity($pps);
            }
            $subscriptions = $prolongProductSubscriptions; // use list from prev product to prolong dates already paid
        }

        $subscription = $strategy->createSubscription($product, $subscriptions);

        $subscription->setStrategy($strategyName);
        $subscription->setUser($user);

        return $subscription;
    }

    /**
     * Activate subscription.
     *
     * @param SubscriptionInterface $subscription
     * @param boolean               $isRenew
     *
     * @throws SubscriptionIntegrityException
     * @throws StrategyNotFoundException
     * @throws SubscriptionStatusException
     * @throws ProductFinalNotValidException
     */
    public function activate(SubscriptionInterface $subscription, bool $isRenew = false): void
    {
        $this->checkSubscriptionIntegrity($subscription);
        $this->checkSubscriptionNonActive($subscription);

        $strategy     = $this->getStrategyFromSubscription($subscription);
        $finalProduct = $strategy->getProductStrategy()->getFinalProduct($subscription->getProduct());

        $subscription->setProduct($finalProduct);
        $subscription->activate();

        $subscriptionEvent = new SubscriptionEvent($subscription, $isRenew);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::ACTIVATE_SUBSCRIPTION);
    }

    /**
     * Renew subscription.
     *
     * @param SubscriptionInterface $subscription
     *
     * @return SubscriptionInterface New subscription
     *
     * @throws SubscriptionIntegrityException
     * @throws SubscriptionRenewalException
     * @throws SubscriptionStatusException
     * @throws StrategyNotFoundException
     * @throws ProductFinalNotValidException
     * @throws PermanentSubscriptionException
     */
    public function renew(SubscriptionInterface $subscription): SubscriptionInterface
    {
        $this->checkSubscriptionIntegrity($subscription);
        $this->checkSubscriptionRenewable($subscription);
        $this->checkSubscriptionActive($subscription);

        // get the next renewal product
        $renewalProduct = $this->getRenewalProduct($subscription->getProduct());
        $strategy = $this->getStrategyFromSubscription($subscription);
        $finalProduct = $strategy->getProductStrategy()->getFinalProduct($renewalProduct);

        // prolong prev product dates, when product is changed (action/trial product is switched with new one)
        $prolongProduct = null;
        $originalProduct = $subscription->getProduct();
        if ($finalProduct !== $originalProduct) {
            $prolongProduct = $originalProduct;
        }

        // create new subscription (following the way of expired subscription)
        $newSubscription = $this->create($finalProduct, $subscription->getUser(), null, $prolongProduct);
        $newSubscription->setAutoRenewal(true);

        // expire current subscription
        $this->expire($subscription, Reason::renew, true);

        // activate the next subscription
        $this->activate($newSubscription, true);

        $subscriptionEvent = new SubscriptionEvent($newSubscription);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::RENEW_SUBSCRIPTION);

        return $newSubscription;
    }

    /**
     * Expire subscription.
     */
    public function expire(SubscriptionInterface $subscription, Reason $reason = Reason::expire, bool $isRenew = false): void
    {
        $subscription->setReason($this->config['reasons'][$reason->value]);
        $subscription->deactivate();

        $subscriptionEvent = new SubscriptionEvent($subscription, $isRenew);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::EXPIRE_SUBSCRIPTION);
    }

    /**
     * Stop auto-renew, provide user all benefits until it ends
     */
    public function stopAutoRenew(SubscriptionInterface $subscription): void
    {
        $subscription->setReason($this->config['reasons'][Reason::stopAutoRenew->value]);
        $subscription->setAutoRenewal(false);

        $subscriptionEvent = new SubscriptionEvent($subscription);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::STOP_AUTO_RENEW_SUBSCRIPTION);
    }

    /**
     * Cancel subscription, instant stop.
     *
     * @throws SubscriptionStatusException
     */
    public function cancel(SubscriptionInterface $subscription): void
    {
        $this->checkSubscriptionActive($subscription);

        $subscription->setReason($this->config['reasons'][Reason::cancel->value]);
        $subscription->deactivate();

        $subscriptionEvent = new SubscriptionEvent($subscription);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::CANCEL_SUBSCRIPTION);
    }

    protected function getRenewalProduct(ProductInterface $product): ProductInterface
    {
        if (null === $product->getNextRenewalProduct()) {
            return $product;
        }

        return $product->getNextRenewalProduct();
    }

    /**
     * Get strategy from subscription.
     *
     * @param SubscriptionInterface $subscription
     * @return SubscriptionStrategyInterface
     * @throws StrategyNotFoundException
     */
    private function getStrategyFromSubscription(SubscriptionInterface $subscription): SubscriptionStrategyInterface
    {
        return $this->registry->get($subscription->getStrategy());
    }

    /**
     * Check subscription integrity.
     *
     * @param SubscriptionInterface $subscription
     * @throws SubscriptionIntegrityException
     */
    private function checkSubscriptionIntegrity(SubscriptionInterface $subscription): void
    {
        if (null === $subscription->getProduct()) {
            throw new SubscriptionIntegrityException('Subscription must have a product defined.');
        }

        if (null === $subscription->getUser()) {
            throw new SubscriptionIntegrityException('Subscription must have a user defined.');
        }
    }

    /**
     * Check if subscription is auto-renewable.
     *
     * @param SubscriptionInterface $subscription
     * @throws SubscriptionRenewalException
     */
    private function checkSubscriptionRenewable(SubscriptionInterface $subscription): void
    {
        if (null === $subscription->getEndDate()) {
            throw new SubscriptionRenewalException('A permanent subscription can not be renewed.');
        }

        if (!$subscription->isAutoRenewal()) {
            throw new SubscriptionRenewalException('The current subscription is not auto-renewal.');
        }

        if (!$subscription->getProduct()->isAutoRenewal()) {
            throw new SubscriptionRenewalException(sprintf(
                'The product "%s" is not auto-renewal. Maybe is disabled?',
                $subscription->getProduct()->getName()
            ));
        }
    }

    /**
     * @param SubscriptionInterface $subscription
     * @throws SubscriptionStatusException
     */
    private function checkSubscriptionNonActive(SubscriptionInterface $subscription): void
    {
        if (!$subscription->isActive()) {
            return;
        }

        throw new SubscriptionStatusException('Subscription is active.');
    }

    /**
     * @param SubscriptionInterface $subscription
     * @throws SubscriptionStatusException
     */
    private function checkSubscriptionActive(SubscriptionInterface $subscription): void
    {
        if ($subscription->isActive()) {
            return;
        }

        throw new SubscriptionStatusException('Subscription is not active.');
    }
}
