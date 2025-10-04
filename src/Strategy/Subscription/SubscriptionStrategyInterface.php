<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Subscription;

use Thinkawitch\SubscriptionBundle\Exception\Strategy\CreateSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Strategy\RenewSubscriptionException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Product\ProductStrategyInterface;

interface SubscriptionStrategyInterface
{
    /**
     * @param ProductInterface $product
     * @param SubscriptionInterface|null $continueFromSubscription
     * @return SubscriptionInterface
     * @throws CreateSubscriptionException
     */
    public function createSubscription(
        ProductInterface $product,
        ?SubscriptionInterface $continueFromSubscription=null,
    ): SubscriptionInterface;

    /**
     * @param ProductInterface $product
     * @param SubscriptionInterface $subscription
     * @return void
     * @throws RenewSubscriptionException
     */
    public function renewSubscription(
        ProductInterface $product,
        SubscriptionInterface $subscription,
    ): void;

    public function getProductStrategy(): ProductStrategyInterface;
}
