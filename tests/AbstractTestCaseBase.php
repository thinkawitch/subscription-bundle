<?php

namespace Thinkawitch\SubscriptionBundle\Tests;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use SlopeIt\ClockMock\ClockMock;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Repository\ProductRepositoryInterface;
use Thinkawitch\SubscriptionBundle\Repository\SubscriptionRepositoryInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Product\ProductDefaultStrategy;
use Thinkawitch\SubscriptionBundle\Strategy\Product\ProductExactStrategy;
use Thinkawitch\SubscriptionBundle\Strategy\Product\ProductStrategyInterface;
use Thinkawitch\SubscriptionBundle\Tests\Mock\UserMock;

abstract class AbstractTestCaseBase extends TestCase
{
    protected \DateInterval $interval1m;
    protected Logger $logger;
    protected ProductRepositoryInterface $productRepository;
    protected SubscriptionRepositoryInterface $subscriptionRepository;
    protected UserMock $user1;
    protected UserMock $user2;
    protected UserMock $user3;
    protected UserMock $user4;
    protected \DateTimeImmutable $subscription1EndDate;
    protected \DateTimeImmutable $subscription2EndDate;
    protected SubscriptionInterface $subscription1U1;
    protected SubscriptionInterface $subscription2U2;
    protected SubscriptionInterface $subscription3U2;
    protected SubscriptionInterface $subscription4U1;
    protected SubscriptionInterface $subscription5U4;
    protected SubscriptionInterface $subscription6U3perm;
    protected SubscriptionInterface $subscription7U4perm;

    protected ProductStrategyInterface $defaultProductStrategy;
    protected ProductStrategyInterface $exactProductStrategy;

    protected ProductInterface $product1;
    protected ProductInterface $product2;

    protected function setUp(): void
    {
        // modify the date
        ClockMock::freeze(new \DateTime('2025-09-15 10:12:59'));
        //exit(date('Y-m-d H:i:s'));

        // logger
        $this->logger = \Mockery::mock(Logger::class);
        $this->logger->shouldReceive('error');
        $this->logger->shouldReceive('log');
        $this->logger->shouldReceive('warning');

        // 1 month
        $this->interval1m = new \DateInterval('P1M');

        // users
        $this->user1 = new UserMock();
        $this->user2 = new UserMock();
        $this->user3 = new UserMock();
        $this->user4 = new UserMock();

        // products
        // default auto-renewal
        $this->product1 = \Mockery::mock(ProductInterface::class);
        $this->product1->shouldReceive('getName')->andReturn('Product 1 Default');
        $this->product1->shouldReceive('isAutoRenewal')->andReturn(true);
        $this->product1->shouldReceive('isDisabled')->andReturn(false);
        $this->product1->shouldReceive('isDefault')->andReturn(true);
        $this->product1->shouldReceive('getDuration')->andReturn($this->interval1m);
        $this->product1->shouldReceive('getExpirationDate')->andReturn(null);
        $this->product1->shouldReceive('getQuota')->andReturn(null);
        $this->product1->shouldReceive('getStrategy')->andReturn('end_last_default');
        // 2nd without auto-renewal
        $this->product2 = \Mockery::mock(ProductInterface::class);
        $this->product2->shouldReceive('getName')->andReturn('Product 2');
        $this->product2->shouldReceive('isAutoRenewal')->andReturn(false);
        $this->product2->shouldReceive('isDisabled')->andReturn(false);
        $this->product2->shouldReceive('isDefault')->andReturn(false);
        $this->product2->shouldReceive('getDuration')->andReturn($this->interval1m);
        $this->product2->shouldReceive('getExpirationDate')->andReturn(null);
        $this->product2->shouldReceive('getQuota')->andReturn(null);
        $this->product2->shouldReceive('getStrategy')->andReturn('end_last_default');

        // product repository
        $this->productRepository = \Mockery::mock(ProductRepositoryInterface::class);
        $this->productRepository->shouldReceive('findDefaultProduct')->andReturn($this->product1);

        // datetime modify() may loose 1 day if different periods used at once, or weekdays: ie +1m7day or +7day1m

        // subscriptions
        $this->subscription1EndDate = new \DateTimeImmutable();
        $this->subscription1U1 = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription1U1->shouldReceive('getEndDate')->andReturn($this->subscription1EndDate);
        $this->subscription1U1->shouldReceive('getUser')->andReturn($this->user1);
        $this->subscription1U1->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription1U1->shouldReceive('isActive')->andReturn(true);

        $this->subscription2EndDate = new \DateTimeImmutable('+10 days');
        $this->subscription2U2 = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription2U2->shouldReceive('getEndDate')->andReturn($this->subscription2EndDate->modify('+7 days'));
        $this->subscription2U2->shouldReceive('getUser')->andReturn($this->user2);
        $this->subscription2U2->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription2U2->shouldReceive('isActive')->andReturn(true);

        $this->subscription3U2 = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription3U2->shouldReceive('getEndDate')->andReturn($this->subscription2EndDate->modify('+3 days'));
        $this->subscription3U2->shouldReceive('getUser')->andReturn($this->user2);
        $this->subscription3U2->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription3U2->shouldReceive('isActive')->andReturn(true);

        $this->subscription4U1 = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription4U1->shouldReceive('getStartDate')->andReturn($this->subscription1EndDate);
        $this->subscription4U1->shouldReceive('getEndDate')->andReturn($this->subscription1EndDate->modify('+15 days'));
        $this->subscription4U1->shouldReceive('getUser')->andReturn($this->user1);
        $this->subscription4U1->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription4U1->shouldReceive('isActive')->andReturn(true);

        $this->subscription5U4 = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription5U4->shouldReceive('getEndDate')->andReturn(new \DateTimeImmutable('+5 days'));
        $this->subscription5U4->shouldReceive('getUser')->andReturn($this->user4);
        $this->subscription5U4->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription5U4->shouldReceive('isActive')->andReturn(true);

        $this->subscription6U3perm = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription6U3perm->shouldReceive('getEndDate')->andReturn(null);
        $this->subscription6U3perm->shouldReceive('getUser')->andReturn($this->user3);
        $this->subscription6U3perm->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription6U3perm->shouldReceive('setStrategy');
        $this->subscription6U3perm->shouldReceive('setUser');
        $this->subscription6U3perm->shouldReceive('isActive')->andReturn(true);

        $this->subscription7U4perm = \Mockery::mock(SubscriptionInterface::class);
        $this->subscription7U4perm->shouldReceive('getEndDate')->andReturn(null);
        $this->subscription7U4perm->shouldReceive('getUser')->andReturn($this->user4);
        $this->subscription7U4perm->shouldReceive('getProduct')->andReturn($this->product2);
        $this->subscription7U4perm->shouldReceive('setStrategy');
        $this->subscription7U4perm->shouldReceive('setUser');
        $this->subscription7U4perm->shouldReceive('isActive')->andReturn(true);

        // subscription repository
        $this->subscriptionRepository = \Mockery::mock(SubscriptionRepositoryInterface::class);

        $this->subscriptionRepository
            ->shouldReceive('findActiveSubscription')
            ->with($this->product2, $this->user1)
            ->andReturn($this->subscription4U1);

        $this->subscriptionRepository
            ->shouldReceive('findActiveSubscription')
            ->with($this->product2, $this->user2)
            ->andReturn($this->subscription2U2);

        $this->subscriptionRepository
            ->shouldReceive('findActiveSubscription')
            ->with($this->product2, $this->user3)
            ->andReturn($this->subscription6U3perm);

        $this->subscriptionRepository
            ->shouldReceive('findActiveSubscription')
            ->with($this->product2, $this->user4)
            ->andReturn($this->subscription7U4perm);

        // product strategies
        $this->defaultProductStrategy = new ProductDefaultStrategy(
            $this->productRepository,
            $this->subscriptionRepository,
            $this->logger,
        );
        $this->exactProductStrategy = new ProductExactStrategy(
            $this->productRepository,
            $this->subscriptionRepository,
            $this->logger,
        );
    }
}
