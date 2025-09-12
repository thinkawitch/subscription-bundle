<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Mock;

use Symfony\Component\Security\Core\User\UserInterface;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;

class SubscriptionMock implements SubscriptionInterface
{
    private UserInterface $user;

    private \DateTimeImmutable $startDate;

    private ?\DateTimeImmutable $endDate;

    private ProductInterface $product;

    private bool $active = false;

    private bool $autoRenewal = false;

    private ?string $reason = null;

    private string $strategy = 'end_last_default';

    public function __construct()
    {
        $this->setUser(new UserMock());
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): SubscriptionMock
    {
        $this->user = $user;
        return $this;
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function setProduct(ProductInterface $product): SubscriptionMock
    {
        $this->product = $product;
        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): SubscriptionMock
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate($endDate): SubscriptionMock
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive($active): SubscriptionMock
    {
        $this->active = $active;
        return $this;
    }

    public function isAutoRenewal(): bool
    {
        return $this->autoRenewal;
    }

    public function setAutoRenewal(bool $autoRenewal): SubscriptionMock
    {
        $this->autoRenewal = $autoRenewal;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): SubscriptionMock
    {
        $this->reason = $reason;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): SubscriptionMock
    {
        $this->setActive(true);
        return $this;
    }

    public function deactivate(): SubscriptionMock
    {
        $this->setActive(false);
        return $this;
    }

    public function setStrategy(string $name): SubscriptionMock
    {
        $this->strategy = $name;
        return $this;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }
}
