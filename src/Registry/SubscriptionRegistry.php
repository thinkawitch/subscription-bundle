<?php

namespace Thinkawitch\SubscriptionBundle\Registry;

use Thinkawitch\SubscriptionBundle\Exception\StrategyNotFoundException;
use Thinkawitch\SubscriptionBundle\Strategy\Subscription\SubscriptionStrategyInterface;

class SubscriptionRegistry
{
    /**
     * @var SubscriptionStrategyInterface[]
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [];
    }

    /**
     * Add strategy.
     *
     * @param SubscriptionStrategyInterface $strategy
     * @param string                        $name
     *
     * @return SubscriptionRegistry
     *
     * @throws \InvalidArgumentException
     */
    public function addStrategy(SubscriptionStrategyInterface $strategy, string $name): self
    {
        if (array_key_exists($name, $this->strategies)) {
            throw new \InvalidArgumentException(sprintf('The strategy %s is already a registered strategy.', $name));
        }

        $this->strategies[$name] = $strategy;

        return $this;
    }

    /**
     * Get strategy.
     *
     * @param string $name
     *
     * @return SubscriptionStrategyInterface
     *
     * @throws StrategyNotFoundException
     */
    public function get(string $name): SubscriptionStrategyInterface
    {
        if (!array_key_exists($name, $this->strategies)) {
            throw new StrategyNotFoundException(sprintf('The strategy "%s" does not exist in the registry', $name));
        }

        return $this->strategies[$name];
    }
}
