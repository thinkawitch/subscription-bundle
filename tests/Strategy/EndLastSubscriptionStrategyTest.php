<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Strategy;

use Thinkawitch\SubscriptionBundle\Exception\Subscription\PermanentSubscriptionException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Strategy\Subscription\SubscriptionEndLastStrategy;
use Thinkawitch\SubscriptionBundle\Tests\AbstractTestCaseBase;
use Thinkawitch\SubscriptionBundle\Tests\Mock\SubscriptionMock;

class EndLastSubscriptionStrategyTest extends AbstractTestCaseBase
{
    public function testExpiredSubscription()
    {
        $currentDate = new \DateTimeImmutable();
        $period = new \DateInterval('P10D');

        // product
        $product = \Mockery::mock(ProductInterface::class);
        $product->shouldReceive('isAutoRenewal')->andReturn(false);
        $product->shouldReceive('getDuration')->andReturn($period);

        // expired subscription
        $subscription1 = \Mockery::mock(SubscriptionInterface::class);
        $subscription1->shouldReceive('getEndDate')->andReturn(new \DateTimeImmutable('2017-04-15 16:05:10'));

        // strategy
        $strategy = new SubscriptionEndLastStrategy(SubscriptionMock::class, $this->defaultProductStrategy);
        $subscription = $strategy->createSubscription($product, [$subscription1]);

        $this->assertEquals(
            $currentDate->format('Y-m-d H:i:s'),
            $subscription->getStartDate()->format('Y-m-d H:i:s'),
            'subscription start date should be today'
        );

        $this->assertEquals(
            $currentDate->add($period)->format('Y-m-d H:i:s'),
            $subscription->getEndDate()->format('Y-m-d H:i:s'),
            'subscription end date should be today + ' . $period->format('%d days')
        );
    }

    public function testNonExpiredSubscription()
    {
        $currentDate = new \DateTimeImmutable();
        $period = new \DateInterval('P10D');

        // product
        $product = \Mockery::mock(ProductInterface::class);
        $product->shouldReceive('isAutoRenewal')->andReturn(false);
        $product->shouldReceive('getDuration')->andReturn($period);

        // active subscription, ends in 5 days
        $subscription1 = \Mockery::mock(SubscriptionInterface::class);
        $subscription1->shouldReceive('getEndDate')->andReturn($currentDate->modify('+5 days'));

        // strategy, renew subscription +10 days
        $strategy = new SubscriptionEndLastStrategy(SubscriptionMock::class, $this->defaultProductStrategy);
        $subscription = $strategy->createSubscription($product, [$subscription1]);

        $this->assertEquals(
            $currentDate->modify('+5 days')->format('Y-m-d H:i:s'),
            $subscription->getStartDate()->format('Y-m-d H:i:s'),
            'subscription start date should be today+5'
        );

        $this->assertEquals(
            $currentDate->modify('+15 days')->format('Y-m-d H:i:s'),
            $subscription->getEndDate()->format('Y-m-d H:i:s'),
            'subscription end date should be today + 15'
        );
    }

    public function testCreatePermanentSubscriptionOnNoActiveSubscriptions()
    {
        // product
        $product = \Mockery::mock(ProductInterface::class);
        $product->shouldReceive('isAutoRenewal')->andReturn(false);
        $product->shouldReceive('getDuration')->andReturn(null);

        // permanent subscription
        $strategy = new SubscriptionEndLastStrategy(SubscriptionMock::class, $this->defaultProductStrategy);
        $subscription = $strategy->createSubscription($product);

        $this->assertEquals(
            null,
            $subscription->getEndDate(),
            'subscription end date should be null (permanent subscription)'
        );
    }

    public function testFailOnMoreThanOnePermanentSubscriptionByProduct()
    {
        $this->expectException(PermanentSubscriptionException::class);

        // active subscriptions
        $subscription1 = \Mockery::mock(SubscriptionInterface::class);
        $subscription1->shouldReceive('getName')->andReturn('Subscription1 with product A');
        $subscription1->shouldReceive('getProduct')->andReturn($this->product);
        $subscription1->shouldReceive('getEndDate')->andReturn(null);

        $subscription2 = \Mockery::mock(SubscriptionInterface::class);
        $subscription2->shouldReceive('getName')->andReturn('Subscription2 with product A');
        $subscription2->shouldReceive('getProduct')->andReturn($this->product);
        $subscription2->shouldReceive('getEndDate')->andReturn(null);

        $strategy = new SubscriptionEndLastStrategy(SubscriptionMock::class, $this->defaultProductStrategy);
        $strategy->createSubscription($this->product, [$subscription1, $subscription2]);
    }

    public function testReturnSameSubscriptionInstanceOnPermanentSubscription()
    {
        // product X
        $productX = \Mockery::mock(ProductInterface::class);

        // active permanent subscription
        $subscription1 = \Mockery::mock(SubscriptionInterface::class);
        $subscription1->shouldReceive('getId')->andReturn(1);
        $subscription1->shouldReceive('getName')->andReturn('Subscription1 with product X');
        $subscription1->shouldReceive('getProduct')->andReturn($productX);
        $subscription1->shouldReceive('getEndDate')->andReturn(null);

        $strategy = new SubscriptionEndLastStrategy(SubscriptionMock::class, $this->defaultProductStrategy);
        $subscription2 = $strategy->createSubscription($this->product, [$subscription1]);

        $this->assertSame(
            $subscription1,
            $subscription2,
            'both subscriptions should be same one object'
        );
    }
}
