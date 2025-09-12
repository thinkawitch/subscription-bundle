<?php

namespace Thinkawitch\SubscriptionBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface;

interface SubscriptionInterface
{
    public function getUser(): UserInterface;

    public function setUser(UserInterface $user): SubscriptionInterface;

    public function getStartDate(): \DateTimeImmutable;

    public function setStartDate(\DateTimeImmutable $dateTime): SubscriptionInterface;

    public function getEndDate(): ?\DateTimeImmutable;

    public function setEndDate(?\DateTimeImmutable $dateTime): SubscriptionInterface;

    public function getProduct(): ProductInterface;

    public function setProduct(ProductInterface $product): SubscriptionInterface;

    public function isActive(): bool;

    public function activate(): SubscriptionInterface;

    public function deactivate(): SubscriptionInterface;

    public function setAutoRenewal(bool $renewal): SubscriptionInterface;

    public function isAutoRenewal(): bool;

    public function setReason(?string $reason): SubscriptionInterface;

    public function getReason(): ?string;

    public function setStrategy(string $name): SubscriptionInterface;

    public function getStrategy(): string;
}
