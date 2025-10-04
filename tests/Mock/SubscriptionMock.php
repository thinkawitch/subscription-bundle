<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionIntervalInterface;

class SubscriptionMock implements SubscriptionInterface
{
    private UserInterface $user;

    private ProductInterface $product;

    private string $strategy = 'end_last_default';

    private Collection $intervals;

    private \DateTimeImmutable $startDate;

    private ?\DateTimeImmutable $endDate;

    private bool $autoRenewal = false;

    private bool $active = false;

    private bool $renewed = false;

    private bool $expired = false;

    private bool $cancelled = false;

    public function __construct()
    {
        $this->intervals = new ArrayCollection();
        $this->setUser(new UserMock());
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;
        return $this;
    }
    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function setStrategy(string $strategy): static
    {
        $this->strategy = $strategy;
        return $this;
    }
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getIntervals(): Collection
    {
        return $this->intervals;
    }
    public function addInterval(SubscriptionIntervalInterface $interval): static
    {
        if (!$this->intervals->contains($interval)) {
            $this->intervals->add($interval);
            $interval->setSubscription($this);
        }
        return $this;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }
    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }
    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setAutoRenewal(bool $autoRenew): static
    {
        $this->autoRenewal = $autoRenew;
        return $this;
    }
    public function isAutoRenewal(): bool
    {
        return $this->autoRenewal;
    }

    public function setActive($active): SubscriptionMock
    {
        $this->active = $active;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
    public function activate(): static
    {
        $this->active = true;
        return $this;
    }
    public function deactivate(): static
    {
        $this->active = false;
        return $this;
    }

    public function isRenewed(): bool
    {
        return $this->renewed;
    }
    public function renew(): static
    {
        $this->renewed = true;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }
    public function expire(): static
    {
        $this->expired = true;
        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
    public function cancel(): static
    {
        $this->cancelled = true;
        return $this;
    }
}
