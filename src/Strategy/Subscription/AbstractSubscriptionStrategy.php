<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Subscription;

use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Product\AbstractProductStrategy;

abstract class AbstractSubscriptionStrategy implements SubscriptionStrategyInterface
{
    public function __construct(
        private readonly string $subscriptionClass,
        private readonly AbstractProductStrategy $productStrategy,
    )
    {
    }

    public function createSubscriptionInstance(): SubscriptionInterface
    {
        return new $this->subscriptionClass();
    }

    public function getProductStrategy(): AbstractProductStrategy
    {
        return $this->productStrategy;
    }

    protected function createCurrentDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
