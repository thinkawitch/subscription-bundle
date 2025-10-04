<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Subscription;

use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionIntervalInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Product\AbstractProductStrategy;

abstract class AbstractSubscriptionStrategy implements SubscriptionStrategyInterface
{
    public function __construct(
        private readonly string $subscriptionClass,
        private readonly string $subscriptionIntervalClass,
        private readonly AbstractProductStrategy $productStrategy,
    )
    {
    }

    public function createSubscriptionInstance(): SubscriptionInterface
    {
        return new $this->subscriptionClass();
    }

    public function createSubscriptionIntervalInstance(): SubscriptionIntervalInterface
    {
        return new $this->subscriptionIntervalClass();
    }

    public function getProductStrategy(): AbstractProductStrategy
    {
        return $this->productStrategy;
    }

    protected function createCurrentDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    protected function createEndDate(\DateTimeImmutable $startDate, ProductInterface $product): ?\DateTimeImmutable
    {
        return  null !== $product->getDuration() ? $startDate->add($product->getDuration()) : null;
    }
}
