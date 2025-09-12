<?php

namespace Thinkawitch\SubscriptionBundle\Repository;

use Thinkawitch\SubscriptionBundle\Model\ProductInterface;

interface ProductRepositoryInterface
{
    public function findDefaultProduct(): ?ProductInterface;
}