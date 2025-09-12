<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Product;

use Thinkawitch\SubscriptionBundle\Exception\Product\ProductDefaultNotFoundException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductDisabledException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductExpiredException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductFinalNotValidException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductIntegrityException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductQuoteExceededException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;

/**
 * Check product is valid,
 * replace it with the default one if issues found
 */
class ProductDefaultStrategy extends AbstractProductStrategy
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
            return $product;

        } catch (
            ProductIntegrityException |
            ProductDisabledException |
            ProductExpiredException |
            ProductQuoteExceededException $exception
        ) {
            $this->error('ProductDefaultStrategy: ' . $exception->getMessage());
        }

        return $this->getDefaultProduct();
    }

    /**
     * Get default product in case of that current product is not valid.
     *
     * @return ProductInterface
     * @throws ProductFinalNotValidException
     */
    private function getDefaultProduct(): ProductInterface
    {
        $defaultProduct = $this->getProductRepository()->findDefaultProduct();

        if (null !== $defaultProduct) {
            return $defaultProduct;
        }

        $message = 'Default product was not found into the product repository';
        $this->critical($message);
        $exception = new ProductDefaultNotFoundException($message);
        throw new ProductFinalNotValidException($message, null, $exception);
    }
}
