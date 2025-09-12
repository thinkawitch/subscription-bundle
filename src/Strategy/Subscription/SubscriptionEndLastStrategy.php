<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Subscription;

use Thinkawitch\SubscriptionBundle\Exception\Subscription\PermanentSubscriptionException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;

/**
 * End Last Subscription Strategy.
 *
 * Starts a new subscription at the end of the latest if there isn't any permanent subscription with the current
 * product.
 */
class SubscriptionEndLastStrategy extends AbstractSubscriptionStrategy
{
    /**
     * @throws PermanentSubscriptionException
     */
    public function createSubscription(ProductInterface $product, array $subscriptions = []): SubscriptionInterface
    {
        if (empty($subscriptions)) {
            return $this->create($this->createCurrentDate(), $product);
        }

        $startDate = null;
        foreach ($subscriptions as $subscription) {

            // subscription is permanent, don't continue
            if (null === $subscription->getEndDate()) {
                $startDate = null;
                break;
            }

            // catch the subscription with higher end date
            if (null === $startDate || $startDate < $subscription->getEndDate()) {
                $startDate = $subscription->getEndDate();
            }
        }

        // it's a permanent subscription
        if (null === $startDate) {

            if (count($subscriptions) > 1) {
                throw new PermanentSubscriptionException(
                    'More than one subscription per product is not allowed when there is a permanent subscription
                    enabled. Maybe you are mixing different strategies?'
                );
            }

            return $subscriptions[0];
        }

        // check if subscription is expired
        if (time() > $startDate->getTimestamp()) {
            $startDate = $this->createCurrentDate();
        }

        // date should use the \DateTimeImmutable (a little fix)
        if (!$startDate instanceof \DateTimeImmutable) {
            $startDate = (new \DateTimeImmutable())->setTimestamp($startDate->getTimestamp());
        }

        return $this->create($startDate, $product);
    }

    private function create(\DateTimeImmutable $startDate, ProductInterface $product): SubscriptionInterface
    {
        $endDate = null !== $product->getDuration() ? $startDate->add($product->getDuration()) : null;

        // create the new subscription
        $subscription = $this->createSubscriptionInstance();
        $subscription->setProduct($product);
        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);
        $subscription->setAutoRenewal($product->isAutoRenewal());

        return $subscription;
    }
}
