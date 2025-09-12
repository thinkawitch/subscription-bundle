<?php

namespace Thinkawitch\SubscriptionBundle\Model;

interface ProductInterface
{
    public function getName(): string;

    public function getDuration(): ?\DateInterval;

    public function getExpirationDate(): ?\DateTime;

    public function getQuota(): ?int;

    public function isDisabled(): bool;

    public function isAutoRenewal(): bool;

    public function isDefault(): bool;

    public function getStrategy(): string;

    public function getNextRenewalProduct(): ?ProductInterface;
}
