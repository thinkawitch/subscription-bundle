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
                ->scalarNode('subscription_interval_class')
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
            ->end();

        return $treeBuilder;
    }
}
