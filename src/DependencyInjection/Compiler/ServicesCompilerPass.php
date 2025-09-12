<?php

namespace Thinkawitch\SubscriptionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $productRepository = $container->getParameter('thinkawitch_subscription.config.product.repository');
        $subscriptionRepository = $container->getParameter('thinkawitch_subscription.config.subscription.repository');

        $container->setAlias('thinkawitch.subscription.repository.product', $productRepository);
        $container->setAlias('thinkawitch.subscription.repository.subscription', $subscriptionRepository);
    }
}
