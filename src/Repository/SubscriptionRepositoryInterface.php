<?php

namespace Thinkawitch\SubscriptionBundle\Repository;

use Symfony\Component\Security\Core\User\UserInterface;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;

interface SubscriptionRepositoryInterface
{
    /**
     * Get number of subscriptions with associated product without regard to the state.
     *
     * @param ProductInterface $product
     *
     * @return integer
     */
    public function getNumberOfSubscriptionsByProduct(ProductInterface $product): int;

    /**
     * Find subscriptions by product and state.
     *
     * @param ProductInterface $product
     * @param UserInterface    $user
     * @param boolean          $active
     *
     * @return SubscriptionInterface[]
     */
    public function findSubscriptionsByProduct(ProductInterface $product, UserInterface $user, bool $active = true): array;

    /**
     * Find subscription by its ID.
     *
     * @param int|string $id
     *
     * @return ?SubscriptionInterface
     */
    public function findSubscriptionById(int|string $id): ?SubscriptionInterface;
}
