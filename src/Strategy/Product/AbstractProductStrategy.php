<?php

namespace Thinkawitch\SubscriptionBundle\Strategy\Product;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductDisabledException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductExpiredException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductIntegrityException;
use Thinkawitch\SubscriptionBundle\Exception\Product\ProductQuoteExceededException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Repository\ProductRepositoryInterface;
use Thinkawitch\SubscriptionBundle\Repository\SubscriptionRepositoryInterface;

abstract class AbstractProductStrategy implements ProductStrategyInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly ?LoggerInterface $logger = null,
    )
    {
    }

    protected function getProductRepository(): ProductRepositoryInterface
    {
        return $this->productRepository;
    }

    protected function getSubscriptionRepository(): SubscriptionRepositoryInterface
    {
        return $this->subscriptionRepository;
    }

    /**
     * Check the product model integrity.
     *
     * @param ProductInterface $product
     * @throws ProductIntegrityException
     */
    final public function checkProductIntegrity(ProductInterface $product): void
    {
        if ($product->isDefault() && null !== $product->getQuota()) {
            throw new ProductIntegrityException(sprintf(
                'The product "%s" is a default product with a quota (%s). Default products can not have a quote value.',
                $product->getName(),
                $product->getQuota()
            ));
        }

        if ($product->isDefault() && null !== $product->getExpirationDate()) {
            throw new ProductIntegrityException(sprintf(
                'The product "%s" is a default product with expiration date (%s). Default products can not have a expiration date.',
                $product->getName(),
                $product->getExpirationDate()->format('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Check product disabled.
     *
     * @param ProductInterface $product
     * @throws ProductDisabledException
     */
    public function checkDisabled(ProductInterface $product): void
    {
        if (!$product->isDisabled()) {
            return;
        }

        throw new ProductDisabledException(sprintf(
            'The product "%s" is disabled.',
            $product->getName(),
        ));
    }

    /**
     * Check product expiration.
     *
     * @param ProductInterface $product
     * @throws ProductExpiredException
     */
    public function checkExpiration(ProductInterface $product): void
    {
        $expirationDate = $product->getExpirationDate();

        if (null === $expirationDate || new \DateTime() <= $expirationDate) {
            return;
        }

        throw new ProductExpiredException(sprintf(
            'The product "%s" has been expired at %s.',
            $product->getName(),
            $expirationDate->format('Y-m-d H:i:s')
        ));
    }

    /**
     * Check product quote.
     *
     * @param ProductInterface $product
     * @throws ProductQuoteExceededException
     */
    public function checkQuote(ProductInterface $product): void
    {
        // unlimited quote
        if (null === $product->getQuota()) {
            return;
        }

        // calculate the current quote
        $currentQuote = $this->getSubscriptionRepository()->getNumberOfSubscriptionsByProduct($product);

        if ($currentQuote < $product->getQuota()) {
            return;
        }

        throw new ProductQuoteExceededException(sprintf(
            'The product "%s" quota is %s. This is exceeded. Increase the quota.',
            $product->getName(),
            $product->getQuota()
        ));
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }
}
