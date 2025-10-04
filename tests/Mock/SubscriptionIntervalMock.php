<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Mock;

use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionIntervalInterface;

class SubscriptionIntervalMock implements SubscriptionIntervalInterface
{
    private SubscriptionInterface $subscription;

    private \DateTimeImmutable $startDate;

    private ?\DateTimeImmutable $endDate = null;

    private bool $active = false;

    private bool $renewed = false;

    private bool $expired = false;

    private bool $cancelled = false;

    public function setSubscription(SubscriptionInterface $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }
    public function getSubscription(): SubscriptionInterface
    {
        return $this->subscription;
    }

    public function setStartDate(\DateTimeImmutable $dateTime): self
    {
        $this->startDate = $dateTime;
        return $this;
    }
    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setEndDate(?\DateTimeImmutable $dateTime): self
    {
        $this->endDate = $dateTime;
        return $this;
    }
    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->active = $isActive;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setIsRenewed(bool $isRenewed): self
    {
        $this->renewed = $isRenewed;
        return $this;
    }
    public function isRenewed(): bool
    {
        return $this->renewed;
    }

    public function setIsExpired(bool $isExpired): self
    {
        $this->expired = $isExpired;
        return $this;
    }
    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function setIsCancelled(bool $isCancelled): self
    {
        $this->cancelled = $isCancelled;
        return $this;
    }
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

}
