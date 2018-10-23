<?php

namespace IDCI\Bundle\GraphQLClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('idci_graphql_client');
        $rootNode
            ->children()
                ->booleanNode('cache_enabled')->end()
                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('http_client')->end()
                            ->scalarNode('cache')->end()
                            ->scalarNode('cache_ttl')->defaultValue(3600)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
