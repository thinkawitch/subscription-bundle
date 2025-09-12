<?php

namespace Thinkawitch\SubscriptionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('thinkawitch_subscription');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('subscription_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()

                ->scalarNode('subscription_repository')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('product_repository')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('logger')
                    ->defaultNull()
                    ->info('LoggerInterface should be set here')
                ->end()

                ->arrayNode('reasons')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('renew')
                            ->defaultValue('Subscription renewed (expired or not)')
                        ->end()
                        ->scalarNode('expire')
                            ->defaultValue('Subscription expired')
                        ->end()
                        ->scalarNode('stop_auto_renew')
                            ->defaultValue('Subscription auto-renew stopped')
                        ->end()
                        ->scalarNode('cancel')
                            ->defaultValue('Subscription cancelled')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
