<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Subscription;

use Thinkawitch\SubscriptionBundle\Exception\Strategy\CreateSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Strategy\RenewSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\PermanentSubscriptionException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionIntervalInterface;

/**
 * End Last Subscription Strategy.
 *
 * Starts a new subscription at the end of the latest if there isn't any permanent subscription with the current
 * product.
 */
class SubscriptionEndLastStrategy extends AbstractSubscriptionStrategy
{
    /**
     * @throws CreateSubscriptionException
     */
    public function createSubscription(
        ProductInterface $product,
        ?SubscriptionInterface $continueFromSubscription=null,
    ): SubscriptionInterface
    {
        if (!$continueFromSubscription) {
            return $this->_createSubscription($product, $this->createCurrentDate());
        }

        if ($continueFromSubscription->isActive() && $continueFromSubscription->getEndDate() === null) {
            $message = 'Only one permanent subscription per product is allowed.';
            $exception = new PermanentSubscriptionException($message);
            throw new CreateSubscriptionException($message, previous: $exception);
        }

        $startDate = $continueFromSubscription->getEndDate();

        // check if subscription is expired
        if (time() > $startDate->getTimestamp()) {
            $startDate = $this->createCurrentDate();
        }

        return $this->_createSubscription($product, $startDate);
    }

    /**
     * @throws RenewSubscriptionException
     */
    public function renewSubscription(ProductInterface $product, SubscriptionInterface $subscription): void
    {
        if ($subscription->getEndDate() === null) {
            $message = 'Permanent subscription cannot be renewed.';
            $exception = new PermanentSubscriptionException($message);
            throw new RenewSubscriptionException($message, previous: $exception);
        }

        $startDate = $subscription->getEndDate();

        // check if subscription is expired
        if (time() > $startDate->getTimestamp()) {
            $startDate = $this->createCurrentDate();
        }

        // add new interval
        $interval = $this->_createSubscriptionInterval($startDate, $this->createEndDate($startDate, $product));
        $subscription->addInterval($interval);

        // update end date
        $subscription->setEndDate($interval->getEndDate());
    }

    private function _createSubscription(ProductInterface $product, \DateTimeImmutable $startDate): SubscriptionInterface
    {
        $endDate = $this->createEndDate($startDate, $product);

        // create the new subscription
        $subscription = $this->createSubscriptionInstance();
        $subscription->setProduct($product);
        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);
        $subscription->setAutoRenewal($product->isAutoRenewal());

        // add interval
        $interval = $this->_createSubscriptionInterval($startDate, $endDate);
        $subscription->addInterval($interval);

        return $subscription;
    }

    private function _createSubscriptionInterval(\DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate): SubscriptionIntervalInterface
    {
        return $this->createSubscriptionIntervalInstance()->setStartDate($startDate)->setEndDate($endDate);
    }
}
