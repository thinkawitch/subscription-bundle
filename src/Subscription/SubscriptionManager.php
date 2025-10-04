<?php

namespace Thinkawitch\SubscriptionBundle\Subscription;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Thinkawitch\SubscriptionBundle\Event\SubscriptionEvent;
use Thinkawitch\SubscriptionBundle\Event\SubscriptionEvents;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductFinalNotValidException;
use Thinkawitch\SubscriptionBundle\Exception\Strategy\CreateSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Strategy\RenewSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Strategy\StrategyNotFoundException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionIntegrityException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionRenewalException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionStatusException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionIntervalInterface;
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
     * @param ?SubscriptionInterface $continueFromSubscription to set start date from the other product subscription
     *
     * @return SubscriptionInterface
     *
     * @throws StrategyNotFoundException
     * @throws SubscriptionIntegrityException
     * @throws CreateSubscriptionException
     */
    public function create(
        ProductInterface $product,
        UserInterface $user,
        ?string $strategyName = null,
        ?SubscriptionInterface $continueFromSubscription = null, // to set start date from other subscription
    ): SubscriptionInterface
    {
        $strategyName = $strategyName ?? $product->getStrategy();
        $strategy = $this->registry->get($strategyName);

        $activeSubscription = $this->subscriptionRepository->findActiveSubscription($product, $user);
        if ($activeSubscription) {
            $this->checkSubscriptionIntegrity($activeSubscription);
        }
        if ($continueFromSubscription) {
            $this->checkSubscriptionIntegrity($continueFromSubscription);
        }
        $continueFromSubscription = $continueFromSubscription ?? $activeSubscription;

        $subscription = $strategy->createSubscription($product, $continueFromSubscription);
        $subscription->setStrategy($strategyName);
        $subscription->setUser($user);

        return $subscription;
    }

    /**
     * Activate subscription.
     *
     * @param SubscriptionInterface $subscription
     * @param bool $isRenew
     * @param bool $isProlong  newly created to prolong for other product
     *
     * @throws ProductFinalNotValidException
     * @throws StrategyNotFoundException
     * @throws SubscriptionIntegrityException
     * @throws SubscriptionStatusException
     */
    public function activate(SubscriptionInterface $subscription, bool $isRenew=false, bool $isProlong=false): void
    {
        $this->checkSubscriptionIntegrity($subscription);
        if ($isRenew) {
            $this->checkSubscriptionActive($subscription);
        } else if ($isProlong) {
            $this->checkSubscriptionNonActive($subscription);
        } else {
            $this->checkSubscriptionNonActive($subscription);
        }

        $strategy = $this->getStrategyFromSubscription($subscription);
        $finalProduct = $strategy->getProductStrategy()->getFinalProduct($subscription->getProduct());

        $subscription->setProduct($finalProduct);
        $subscription->activate();

        /** @var SubscriptionIntervalInterface $interval */
        foreach ($subscription->getIntervals()->getIterator() as $interval) {
            if ($interval->isExpired()) continue;
            if ($interval->isCancelled()) continue;
            $interval->setIsActive(true);
        }

        $subscriptionEvent = new SubscriptionEvent($subscription, $isRenew||$isProlong);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::ACTIVATE_SUBSCRIPTION);
    }

    /**
     * Renew subscription.
     *
     * @param SubscriptionInterface $subscription
     *
     * @return SubscriptionInterface Same or new subscription
     *
     * @throws SubscriptionIntegrityException
     * @throws SubscriptionRenewalException
     * @throws SubscriptionStatusException
     * @throws StrategyNotFoundException
     * @throws ProductFinalNotValidException
     * @throws CreateSubscriptionException
     * @throws RenewSubscriptionException
     */
    public function renew(SubscriptionInterface $subscription): SubscriptionInterface
    {
        $this->checkSubscriptionIntegrity($subscription);
        $this->checkSubscriptionRenewable($subscription);
        $this->checkSubscriptionActive($subscription);

        // get the next renewal product
        $originalProduct = $subscription->getProduct();
        $renewalProduct = $this->getRenewalProduct($subscription->getProduct());
        $strategy = $this->getStrategyFromSubscription($subscription);
        $finalProduct = $strategy->getProductStrategy()->getFinalProduct($renewalProduct);

        // get strategy
        $strategyName = $finalProduct->getStrategy();
        $strategy = $this->registry->get($strategyName);

        if ($finalProduct === $originalProduct) {
            // just renew
            $strategy->renewSubscription($finalProduct, $subscription);
            $this->activate($subscription, true);
            $subscription->renew();

            $lastInterval = $subscription->getIntervals()->last();
            /** @var SubscriptionIntervalInterface $interval */
            foreach ($subscription->getIntervals()->getIterator() as $interval) {
                if (!$interval->isActive()) continue;
                if ($interval === $lastInterval) continue; // leave last one as not renewed yet
                $interval->setIsRenewed(true);
            }
        } else {
            // product is changed (action/trial product is switched with new one)
            // close subscription and create a new one
            $subscription->renew();
            $subscription->deactivate();

            $newSubscription = $this->create($finalProduct, $subscription->getUser(), $strategyName, $subscription);
            $this->activate($newSubscription, false, true);

            $subscription = $newSubscription; // further to work with new subscription
        }

        $subscriptionEvent = new SubscriptionEvent($subscription);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::RENEW_SUBSCRIPTION);

        return $subscription;
    }

    /**
     * Stop auto-renew, provide user all benefits until it ends
     */
    public function stopAutoRenew(SubscriptionInterface $subscription): void
    {
        $subscription->setAutoRenewal(false);

        $subscriptionEvent = new SubscriptionEvent($subscription);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::STOP_AUTO_RENEW_SUBSCRIPTION);
    }

    /**
     * Expire subscription.
     */
    public function expire(SubscriptionInterface $subscription, bool $isRenew = false): void
    {
        $subscription->expire();
        if ($isRenew) $subscription->renew();
        $subscription->deactivate();

        $now = new \DateTimeImmutable();
        $foundExpiredInterval = false;
        /** @var SubscriptionIntervalInterface $interval */
        foreach ($subscription->getIntervals()->getIterator() as $interval) {
            if ($now >= $interval->getStartDate() && $now <= $interval->getEndDate()) {
                $interval->setIsExpired(true);
                $interval->setIsActive(false);
                $foundExpiredInterval = true;
            }
        }
        if (!$foundExpiredInterval) {
            $lastInterval = $subscription->getIntervals()->last();
            if ($lastInterval) {
                $interval->setIsExpired(true);
                $interval->setIsActive(false);
            }
        }

        $subscriptionEvent = new SubscriptionEvent($subscription, $isRenew);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::EXPIRE_SUBSCRIPTION);
    }


    /**
     * Cancel subscription, instant stop.
     *
     * @throws SubscriptionStatusException
     */
    public function cancel(SubscriptionInterface $subscription): void
    {
        $this->checkSubscriptionActive($subscription);

        $subscription->cancel();
        $subscription->deactivate();

        $now = new \DateTimeImmutable();
        /** @var SubscriptionIntervalInterface $interval */
        foreach ($subscription->getIntervals()->getIterator() as $interval) {
            $markAsCancelled = false;
            if ($now >= $interval->getStartDate() && $now <= $interval->getEndDate()) { // current interval
                $markAsCancelled = true;
            }
            if ($now <= $interval->getEndDate()) { // future interval
                $markAsCancelled = true;
            }
            if ($interval->getEndDate() === null) { // permanent subscription
                $markAsCancelled = true;
            }
            if ($markAsCancelled) {
                $interval->setIsCancelled(true);
                $interval->setIsActive(false);
            }
        }

        $subscriptionEvent = new SubscriptionEvent($subscription);
        $this->eventDispatcher->dispatch($subscriptionEvent, SubscriptionEvents::CANCEL_SUBSCRIPTION);
    }

    protected function getRenewalProduct(ProductInterface $product): ProductInterface
    {
        return $product->getNextRenewalProduct() ?? $product;
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
