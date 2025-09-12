<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Subscription;

use Thinkawitch\SubscriptionBundle\Exception\Subscription\PermanentSubscriptionException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Product\ProductStrategyInterface;

interface SubscriptionStrategyInterface
{
    /**
     * Create new subscription.
     *
     * @param ProductInterface        $product       Product that will be used to create the new subscription
     * @param SubscriptionInterface[] $subscriptions Enabled subscriptions
     * @return SubscriptionInterface
     * @throws PermanentSubscriptionException
     */
    public function createSubscription(ProductInterface $product, array $subscriptions = []): SubscriptionInterface;

    public function getProductStrategy(): ProductStrategyInterface;
}
