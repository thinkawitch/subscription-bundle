<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Thinkawitch\SubscriptionBundle\Repository\SubscriptionRepositoryInterface;
use Thinkawitch\SubscriptionBundle\Subscription\SubscriptionManager;
use Thinkawitch\SubscriptionBundle\Tests\Mock\SubscriptionMock;

class CommandTestCase extends TestCase
{
    protected function getMockContainer()
    {
        // manager
        $manager = \Mockery::mock(SubscriptionManager::class)
            ->shouldReceive('activate')->once()
            ->shouldReceive('cancel')->once()
            ->shouldReceive('expire')->once()
            ->getMock();

        // repository
        $repository = \Mockery::mock(SubscriptionRepositoryInterface::class)
            ->shouldReceive('findSubscriptionById')
            ->withAnyArgs()
            ->andReturn(new SubscriptionMock())
            ->getMock();

        // entity manager
        $em = \Mockery::mock(EntityManagerInterface::class)
            ->shouldReceive('flush')
            ->withAnyArgs()
            ->andReturnNull()
            ->getMock();

        // container
        $container = \Mockery::mock(Container::class);
        $container
            ->shouldReceive('get')
            ->once()
            ->with('thinkawitch.subscription.repository.subscription')
            ->andReturn($repository);

        $container
            ->shouldReceive('get')
            ->with('thinkawitch.subscription.manager')
            ->andReturn($manager);

        $container
            ->shouldReceive('get')
            ->once()
            ->with('entity_manager')
            ->andReturn($em);

        return $container;
    }
}
