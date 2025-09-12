<?php

namespace Thinkawitch\SubscriptionBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;

class SubscriptionEvent extends Event
{
    public function __construct(
        protected SubscriptionInterface $subscription,
        protected readonly bool $fromRenew = false,
    )
    {
    }

    public function getSubscription(): SubscriptionInterface
    {
        return $this->subscription;
    }

    public function isFromRenew(): bool
    {
        return $this->fromRenew;
    }
}
