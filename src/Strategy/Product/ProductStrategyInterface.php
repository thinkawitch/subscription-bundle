<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Product;

use Thinkawitch\SubscriptionBundle\Exception\Product\ProductFinalNotValidException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;

interface ProductStrategyInterface
{
    /**
     * Get final product.
     * Determine the final based on your own algorithms.
     *
     * @param ProductInterface $product
     * @return ProductInterface
     * @throws ProductFinalNotValidException
     */
    public function getFinalProduct(ProductInterface $product): ProductInterface;
}
