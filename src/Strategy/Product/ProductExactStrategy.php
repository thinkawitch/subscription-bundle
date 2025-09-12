<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Product;

use Thinkawitch\SubscriptionBundle\Exception\Product\ProductDisabledException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductExpiredException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductFinalNotValidException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductIntegrityException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductQuoteExceededException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;

/**
 * Check product is valid,
 * do not replace it with anything else,
 * throw exception further
 */
class ProductExactStrategy extends AbstractProductStrategy
{
    /**
     * @throws ProductFinalNotValidException
     */
    public function getFinalProduct(ProductInterface $product): ProductInterface
    {
        try {
            $this->checkProductIntegrity($product);
            $this->checkDisabled($product);
            $this->checkExpiration($product);
            $this->checkQuote($product);

        } catch (
            ProductIntegrityException |
            ProductDisabledException |
            ProductExpiredException |
            ProductQuoteExceededException $exception
        ) {
            $this->error('ProductExactStrategy: ' . $exception->getMessage());
            throw new ProductFinalNotValidException($exception->getMessage(), null, $exception);
        }

        return $product;
    }
}
