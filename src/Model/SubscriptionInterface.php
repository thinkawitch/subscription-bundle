<?php

namespace Thinkawitch\SubscriptionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

interface SubscriptionInterface
{
    public function getUser(): UserInterface;

    public function setUser(UserInterface $user): self;

    public function getProduct(): ProductInterface;

    public function setProduct(ProductInterface $product): self;

    public function setStrategy(string $name): self;

    public function getStrategy(): string;

    /**
     * Should be ordered as earliest first
     * @return Collection<int, SubscriptionIntervalInterface>
     */
    public function getIntervals(): Collection;
    public function addInterval(SubscriptionIntervalInterface $interval): self;

    public function getStartDate(): \DateTimeImmutable;

    public function setStartDate(\DateTimeImmutable $dateTime): self;

    public function getEndDate(): ?\DateTimeImmutable;

    public function setEndDate(?\DateTimeImmutable $dateTime): self;

    public function isActive(): bool;

    public function activate(): self;

    public function deactivate(): self;

    public function setAutoRenewal(bool $renewal): self;

    public function isAutoRenewal(): bool;

    public function isRenewed(): bool;
    public function renew(): self;

    public function isExpired(): bool;
    public function expire(): self;

    public function isCancelled(): bool;
    public function cancel(): self;

}
