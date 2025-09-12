<?php

namespace Thinkawitch\SubscriptionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SubscriptionStrategyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $factory = $container->findDefinition('thinkawitch.subscription.registry');
        $strategyServiceIds = array_keys($container->findTaggedServiceIds('thinkawitch.subscription.strategy'));

        // add subscription strategies to factory instance
        foreach ($strategyServiceIds as $strategyServiceId) {
            $strategy = $container->findDefinition($strategyServiceId);
            $tag = $strategy->getTag('thinkawitch.subscription.strategy');
            if ('subscription' !== $tag[0]['type']) {
                continue;
            }
            $factory->addMethodCall('addStrategy', [new Reference($strategyServiceId), $tag[0]['strategy']]);
        }
    }
}
