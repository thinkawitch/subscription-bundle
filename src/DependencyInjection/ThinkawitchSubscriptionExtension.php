<?php

namespace Thinkawitch\SubscriptionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class ThinkawitchSubscriptionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yml');
        $loader->load('strategy.product.yml');
        $loader->load('strategy.subscription.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('thinkawitch_subscription.config', $config);
        $container->setParameter('thinkawitch_subscription.config.subscription.class', $config['subscription_class']);
        $container->setParameter('thinkawitch_subscription.config.subscription.repository', $config['subscription_repository']);
        $container->setParameter('thinkawitch_subscription.config.product.repository', $config['product_repository']);

        if (!empty($config['logger'])) {
            $definition = $container->getDefinition('thinkawitch.subscription.strategy.product.abstract');
            $definition->setArgument('$logger', new Reference($config['logger']));
        }
    }
}
