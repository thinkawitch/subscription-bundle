<?php

namespace Thinkawitch\SubscriptionBundle\Model;

interface SubscriptionIntervalInterface
{
    public function getSubscription(): SubscriptionInterface;
    public function setSubscription(SubscriptionInterface $subscription): self;

    public function getStartDate(): \DateTimeImmutable;
    public function setStartDate(\DateTimeImmutable $dateTime): self;

    public function getEndDate(): ?\DateTimeImmutable;
    public function setEndDate(?\DateTimeImmutable $dateTime): self;

    public function isActive(): bool;
    public function setIsActive(bool $isActive): self;

    public function isRenewed(): bool;
    public function setIsRenewed(bool $isRenewed): self;

    public function isExpired(): bool;
    public function setIsExpired(bool $isExpired): self;

    public function isCancelled(): bool;
    public function setIsCancelled(bool $isCancelled): self;

}
