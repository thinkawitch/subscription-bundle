<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Subscription;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\PermanentSubscriptionException;
use Thinkawitch\SubscriptionBundle\Exception\Subscription\SubscriptionRenewalException;
use Thinkawitch\SubscriptionBundle\Model\ProductInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Registry\SubscriptionRegistry;
use Thinkawitch\SubscriptionBundle\Strategy\Subscription\SubscriptionEndLastStrategy;
use Thinkawitch\SubscriptionBundle\Subscription\SubscriptionManager;
use Thinkawitch\SubscriptionBundle\Tests\AbstractTestCaseBase;
use Thinkawitch\SubscriptionBundle\Tests\Mock\SubscriptionMock;

class SubscriptionManagerTest extends AbstractTestCaseBase
{
    private SubscriptionManager $subscriptionManager;

    protected function setUp(): void
    {
        parent::setUp();

        // product
        $this->product->shouldReceive('getName')->andReturn('Test non-default product');
        $this->product->shouldReceive('getDuration')->andReturn($this->interval1m);
        $this->product->shouldReceive('isDefault')->andReturn(false);
        $this->product->shouldReceive('getExpirationDate')->andReturn(null);
        $this->product->shouldReceive('getQuota')->andReturn(null);
        $this->product->shouldReceive('isAutoRenewal')->andReturn(false);

        // Repositories
        $this->productRepository->shouldReceive('findDefaultProduct')->andReturn($this->product);
        $this->subscriptionRepository->shouldReceive('getNumberOfSubscriptionsByProduct')->andReturn(50);

        // Registry
        $registry = new SubscriptionRegistry();
        $registry->addStrategy(
            new SubscriptionEndLastStrategy(SubscriptionMock::class, $this->defaultProductStrategy),
            'end_last_default'
        );

        $eventDispatcher = \Mockery::mock(EventDispatcher::class);
        $eventDispatcher->shouldReceive('dispatch');

        // Manager
        $this->subscriptionManager = new SubscriptionManager($registry, $this->subscriptionRepository, $eventDispatcher, [
            'reasons' => [
                'renew' => 'RENEW_TEXT',
                'expire' => 'EXPIRE_TEXT',
                'stop_auto_renew' => 'STOP_AUTO_RENEW_TEXT',
                'cancel' => 'CANCEL_TEXT',
            ]
        ]);
    }

    public function testConcatNewSubscriptionWithPrevious()
    {
        $subscription = $this->subscriptionManager->create($this->product, $this->user1);

        $this->assertEquals(
            $this->subscription1EndDate->modify('+15 days')->add($this->interval1m)->format('Y-m-d H:i:s'),
            $subscription->getEndDate()->format('Y-m-d H:i:s'),
            'subscription end date should be today + 1 month 15 days'
        );

        $this->assertSame(
            $this->user1,
            $subscription->getUser(),
            'subscription user should be user1'
        );
        $this->assertEquals(
            false,
            $subscription->isAutoRenewal(),
            'subscription auto renewal should be false'
        );
    }

    public function testConcatNewSubscriptionWithOverlapedSubscriptions()
    {
        $subscription = $this->subscriptionManager->create($this->product, $this->user2);

        $this->assertEquals(
            $this->subscription2EndDate->modify('+7 day')->add($this->interval1m)->format('Y-m-d H:i:s'),
            //$this->subscription2EndDate->modify('+1 month 7 day')->format('Y-m-d H:i:s'), // will fail if today 2025-09-15 10:12:59
            $subscription->getEndDate()->format('Y-m-d H:i:s'),
            'subscription end date should be today + 1 month 7 days'
        );

        $this->assertSame(
            $this->user2,
            $subscription->getUser(),
            'subscription user should be user2'
        );
        $this->assertEquals(
            false,
            $subscription->isAutoRenewal(),
            'subscription auto renewal should be false'
        );
    }

    public function testSameSubscriptionOnPermanentSubscription()
    {
        $subscription = $this->subscriptionManager->create($this->product, $this->user3);

        $this->assertInstanceOf(SubscriptionInterface::class, $subscription);
        $this->assertEquals(null, $subscription->getEndDate());
        $this->assertSame($this->permanentSubscription1, $subscription);
        $this->assertSame($this->user3, $subscription->getUser());
    }

    public function testSameSubscriptionOnPermanentWithFiniteSubscriptions()
    {
        $this->expectException(PermanentSubscriptionException::class);

        $this->subscriptionManager->create($this->product, $this->user4);
    }

    public function testActivateSubscriptionWithValidProduct()
    {
        $subscription = new SubscriptionMock();
        $subscription->setActive(false);
        $subscription->setProduct($this->product);

        $this->subscriptionManager->activate($subscription);

        $this->assertEquals(true, $subscription->getActive());
        $this->assertSame($this->product, $subscription->getProduct());
    }

    public function testActivateSubscriptionWithExpiredProductAndDefault()
    {
        $productExpired = \Mockery::mock(ProductInterface::class);
        $productExpired->shouldReceive('isDefault')->andReturn(false);
        $productExpired->shouldReceive('isDisabled')->andReturn(false);
        $productExpired->shouldReceive('getExpirationDate')->andReturn(new \DateTime('-1 hour'));
        $productExpired->shouldReceive('getName')->andReturn('Test non-default product with expiration date');
        $productExpired->shouldReceive('getQuota')->andReturn(null);

        $subscription = new SubscriptionMock();
        $subscription->setProduct($productExpired);

        $this->subscriptionManager->activate($subscription);

        $this->assertEquals(true, $subscription->getActive());
        $this->assertSame($this->product, $subscription->getProduct());
    }

    public function testActivateSubscriptionWithQuoteExceededAndDefault()
    {
        $productQuota = \Mockery::mock(ProductInterface::class);
        $productQuota->shouldReceive('isDefault')->andReturn(false);
        $productQuota->shouldReceive('isDisabled')->andReturn(false);
        $productQuota->shouldReceive('getExpirationDate')->andReturn(null);
        $productQuota->shouldReceive('getName')->andReturn('Test non-default product with quota exceeded');
        $productQuota->shouldReceive('getQuota')->andReturn(50);

        $subscription = new SubscriptionMock();
        $subscription->setProduct($productQuota);

        $this->subscriptionManager->activate($subscription);

        $this->assertEquals(true, $subscription->getActive());
        $this->assertSame($this->product, $subscription->getProduct());
    }

    public function testActivateSubscriptionWithoutQuoteExceededAndDefault()
    {
        $productQuota = \Mockery::mock(ProductInterface::class);
        $productQuota->shouldReceive('isDefault')->andReturn(false);
        $productQuota->shouldReceive('isDisabled')->andReturn(false);
        $productQuota->shouldReceive('getExpirationDate')->andReturn(null);
        $productQuota->shouldReceive('getName')->andReturn('Test non-default product without quota exceeded');
        $productQuota->shouldReceive('getQuota')->andReturn(51);
        $productQuota->shouldReceive('isAutoRenewal')->andReturn(false);

        $subscription = new SubscriptionMock();
        $subscription->setProduct($productQuota);

        $this->subscriptionManager->activate($subscription);

        $this->assertEquals(true, $subscription->getActive());
        $this->assertEquals(false, $subscription->isAutoRenewal());
        $this->assertSame($productQuota, $subscription->getProduct());
    }

    public function testRenewPermanentSubscriptionFail()
    {
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($this->product);
        $subscription->setAutoRenewal(false);
        $subscription->setEndDate(null);

        $this->expectException(SubscriptionRenewalException::class);
        $this->expectExceptionMessage('A permanent subscription can not be renewed.');

        $this->subscriptionManager->renew($subscription);
    }

    public function testRenewSubscriptionNotEnabledAtSubscription()
    {
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($this->product);
        $subscription->setAutoRenewal(false);
        $subscription->setEndDate(new \DateTimeImmutable());

        $this->expectException(SubscriptionRenewalException::class);
        $this->expectExceptionMessage('The current subscription is not auto-renewal.');

        $this->subscriptionManager->renew($subscription);
    }

    public function testRenewSubscriptionNotEnabledASubscription()
    {
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($this->product);
        $subscription->setAutoRenewal(true);
        $subscription->setEndDate(new \DateTimeImmutable());

        $this->expectException(SubscriptionRenewalException::class);
        $this->expectExceptionMessage('The product "'.$this->product->getName().'" is not auto-renewal. Maybe is disabled?');

        $this->subscriptionManager->renew($subscription);
    }

    public function testRenewSubscriptionNotEnableAtProduct()
    {
        // product
        $product = \Mockery::mock(ProductInterface::class);
        $product->shouldReceive('getName')->andReturn('Test non-default product without quota exceeded');
        $product->shouldReceive('getDuration')->andReturn($this->interval1m);
        $product->shouldReceive('isDefault')->andReturn(false);
        $product->shouldReceive('isDisabled')->andReturn(false);
        $product->shouldReceive('getExpirationDate')->andReturn(null);
        $product->shouldReceive('getQuota')->andReturn(null);
        $product->shouldReceive('isAutoRenewal')->andReturn(true);
        $product->shouldReceive('getNextRenewalProduct')->andReturn(null);
        $product->shouldReceive('getStrategy')->andReturn('end_last_default');

        // subscription to renew
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($product);
        $subscription->setAutoRenewal(true);
        $subscription->setEndDate(new \DateTimeImmutable());

        // no current active subscriptions
        $this->subscriptionRepository->shouldReceive('findSubscriptionsByProduct')->andReturn([]);

        // renew subscription
        $newSubscription = $this->subscriptionManager->renew($subscription);

        $this->assertEquals(false, $subscription->isActive());
        $this->assertEquals('RENEW_TEXT', $subscription->getReason());

        $this->assertEquals(true, $newSubscription->isActive());
        $this->assertEquals(true, $newSubscription->isAutoRenewal());
        $this->assertSame($subscription->getUser(), $newSubscription->getUser());
    }

    public function testRenewSubscriptionWithSameProduct()
    {
        // product
        $product = \Mockery::mock(ProductInterface::class);
        $product->shouldReceive('getName')->andReturn('Test non-default product without quota exceeded');
        $product->shouldReceive('getDuration')->andReturn($this->interval1m);
        $product->shouldReceive('isDefault')->andReturn(false);
        $product->shouldReceive('isDisabled')->andReturn(false);
        $product->shouldReceive('getExpirationDate')->andReturn(null);
        $product->shouldReceive('getQuota')->andReturn(null);
        $product->shouldReceive('isAutoRenewal')->andReturn(true);
        $product->shouldReceive('getNextRenewalProduct')->andReturn(null);
        $product->shouldReceive('getStrategy')->andReturn('end_last_default');

        // subscription to renew
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($product);
        $subscription->setAutoRenewal(true);
        $subscription->setEndDate(new \DateTimeImmutable());

        // no current active subscriptions
        $this->subscriptionRepository->shouldReceive('findSubscriptionsByProduct')->andReturn([]);

        // renew subscription
        $newSubscription = $this->subscriptionManager->renew($subscription);

        $this->assertEquals(false, $subscription->isActive());
        $this->assertEquals('RENEW_TEXT', $subscription->getReason());

        $this->assertEquals(true, $newSubscription->isActive());
        $this->assertEquals(true, $newSubscription->isAutoRenewal());
        $this->assertSame($product, $newSubscription->getProduct());
        $this->assertSame($subscription->getUser(), $newSubscription->getUser());
    }

    public function testRenewSubscriptionWithDifferentProduct()
    {
        // product
        $product = \Mockery::mock(ProductInterface::class);
        $product->shouldReceive('getName')->andReturn('Test non-default product without quota exceeded');
        $product->shouldReceive('getDuration')->andReturn($this->interval1m);
        $product->shouldReceive('isDefault')->andReturn(false);
        $product->shouldReceive('isDisabled')->andReturn(true); // product should be switched, so this is acceptable
        $product->shouldReceive('getExpirationDate')->andReturn(null);
        $product->shouldReceive('getQuota')->andReturn(null);
        $product->shouldReceive('isAutoRenewal')->andReturn(true);
        $product->shouldReceive('getNextRenewalProduct')->andReturn($this->product); // This is the important step

        // add to the default product strategy name
        //$this->product->shouldReceive('getStrategy')->andReturn('end_last_default');

        // subscription to renew
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($product);
        $subscription->setAutoRenewal(true);
        $subscription->setEndDate(new \DateTimeImmutable());

        // no current active subscriptions
        $this->subscriptionRepository->shouldReceive('findSubscriptionsByProduct')->andReturn([]);

        // renew subscription
        $newSubscription = $this->subscriptionManager->renew($subscription);

        $this->assertEquals(false, $subscription->isActive());
        $this->assertEquals('RENEW_TEXT', $subscription->getReason());

        $this->assertEquals(true, $newSubscription->isActive());
        $this->assertEquals(true, $newSubscription->isAutoRenewal());
        $this->assertSame($this->product, $newSubscription->getProduct());
    }

    public function testExpireSubscriptionNotEnableAtProduct()
    {
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($this->product);

        $this->subscriptionManager->expire($subscription);

        $this->assertEquals(false, $subscription->getActive());
        $this->assertEquals('EXPIRE_TEXT', $subscription->getReason());
    }

    public function testCancelSubscription()
    {
        $subscription = new SubscriptionMock();
        $subscription->setActive(true);
        $subscription->setProduct($this->product);

        $this->subscriptionManager->cancel($subscription);

        $this->assertEquals(false, $subscription->getActive());
        $this->assertEquals('CANCEL_TEXT', $subscription->getReason());
    }
}
