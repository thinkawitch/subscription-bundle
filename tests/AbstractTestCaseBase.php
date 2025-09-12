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
use Thinkawitch\SubscriptionBundle\Strategy\Product\ProductStrategyInterface;
use Thinkawitch\SubscriptionBundle\Tests\Mock\UserMock;

abstract class AbstractTestCaseBase extends TestCase
{
    protected \DateInterval $interval1m;
    protected Logger $logger;
    protected $productRepository;
    protected SubscriptionRepositoryInterface $subscriptionRepository;
    protected UserMock $user1;
    protected UserMock $user2;
    protected UserMock $user3;
    protected UserMock $user4;
    protected \DateTimeImmutable $subscription1EndDate;
    protected \DateTimeImmutable $subscription2EndDate;
    protected SubscriptionInterface $currentSubscription1;
    protected SubscriptionInterface $currentSubscription2;
    protected SubscriptionInterface $currentSubscription3;
    protected SubscriptionInterface $currentSubscription4;
    protected SubscriptionInterface $currentSubscription5;
    protected SubscriptionInterface $permanentSubscription1;
    protected SubscriptionInterface $permanentSubscription2;
    protected ProductStrategyInterface $defaultProductStrategy;
    protected ProductInterface $product;

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

        // product
        $this->product = \Mockery::mock(ProductInterface::class);
        $this->product->shouldReceive('getName')->andReturn('Product Base');
        $this->product->shouldReceive('isDisabled')->andReturn(false);
        $this->product->shouldReceive('getStrategy')->andReturn('end_last_default');

        // product repository
        $this->productRepository = \Mockery::mock(ProductRepositoryInterface::class);

        // datetime modify() may loose 1 day if different periods used at once, or weekdays: ie +1m7day or +7day1m

        // subscriptions
        $this->subscription1EndDate = new \DateTimeImmutable();
        $this->currentSubscription1 = \Mockery::mock(SubscriptionInterface::class);
        $this->currentSubscription1->shouldReceive('getEndDate')->andReturn($this->subscription1EndDate);
        $this->currentSubscription1->shouldReceive('getUser')->andReturn($this->user1);
        $this->currentSubscription1->shouldReceive('getProduct')->andReturn($this->product);

        $this->subscription2EndDate = new \DateTimeImmutable('+10 days');
        $this->currentSubscription2 = \Mockery::mock(SubscriptionInterface::class);
        $this->currentSubscription2->shouldReceive('getEndDate')->andReturn($this->subscription2EndDate->modify('+7 days'));
        $this->currentSubscription2->shouldReceive('getUser')->andReturn($this->user2);
        $this->currentSubscription2->shouldReceive('getProduct')->andReturn($this->product);

        $this->currentSubscription3 = \Mockery::mock(SubscriptionInterface::class);
        $this->currentSubscription3->shouldReceive('getEndDate')->andReturn($this->subscription2EndDate->modify('+3 days'));
        $this->currentSubscription3->shouldReceive('getUser')->andReturn($this->user2);
        $this->currentSubscription3->shouldReceive('getProduct')->andReturn($this->product);

        $this->currentSubscription4 = \Mockery::mock(SubscriptionInterface::class);
        $this->currentSubscription4->shouldReceive('getStartDate')->andReturn($this->subscription1EndDate);
        $this->currentSubscription4->shouldReceive('getEndDate')->andReturn($this->subscription1EndDate->modify('+15 days'));
        $this->currentSubscription4->shouldReceive('getUser')->andReturn($this->user1);
        $this->currentSubscription4->shouldReceive('getProduct')->andReturn($this->product);

        $this->currentSubscription5 = \Mockery::mock(SubscriptionInterface::class);
        $this->currentSubscription5->shouldReceive('getEndDate')->andReturn(new \DateTimeImmutable('+5 days'));
        $this->currentSubscription5->shouldReceive('getUser')->andReturn($this->user4);
        $this->currentSubscription5->shouldReceive('getProduct')->andReturn($this->product);

        $this->permanentSubscription1 = \Mockery::mock(SubscriptionInterface::class);
        $this->permanentSubscription1->shouldReceive('getEndDate')->andReturn(null);
        $this->permanentSubscription1->shouldReceive('getUser')->andReturn($this->user3);
        $this->permanentSubscription1->shouldReceive('getProduct')->andReturn($this->product);
        $this->permanentSubscription1->shouldReceive('setStrategy');
        $this->permanentSubscription1->shouldReceive('setUser');

        $this->permanentSubscription2 = \Mockery::mock(SubscriptionInterface::class);
        $this->permanentSubscription2->shouldReceive('getEndDate')->andReturn(null);
        $this->permanentSubscription2->shouldReceive('getUser')->andReturn($this->user4);
        $this->permanentSubscription2->shouldReceive('getProduct')->andReturn($this->product);
        $this->permanentSubscription2->shouldReceive('setStrategy');
        $this->permanentSubscription2->shouldReceive('setUser');

        // subscription repository
        $this->subscriptionRepository = \Mockery::mock(SubscriptionRepositoryInterface::class);

        $this->subscriptionRepository
            ->shouldReceive('findSubscriptionsByProduct')
            ->with($this->product, $this->user1)
            ->andReturn([
                $this->currentSubscription1,
                $this->currentSubscription4
            ]);

        $this->subscriptionRepository
            ->shouldReceive('findSubscriptionsByProduct')
            ->with($this->product, $this->user2)
            ->andReturn([
                $this->currentSubscription2,
                $this->currentSubscription3
            ]);

        $this->subscriptionRepository
            ->shouldReceive('findSubscriptionsByProduct')
            ->with($this->product, $this->user3)
            ->andReturn([
                $this->permanentSubscription1
            ]);

        $this->subscriptionRepository
            ->shouldReceive('findSubscriptionsByProduct')
            ->with($this->product, $this->user4)
            ->andReturn([
                $this->currentSubscription4,
                $this->permanentSubscription2
            ]);

        // default product strategy
        $this->defaultProductStrategy = new ProductDefaultStrategy(
            $this->productRepository,
            $this->subscriptionRepository,
            $this->logger
        );
    }
}
