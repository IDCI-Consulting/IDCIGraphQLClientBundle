<?php

namespace IDCI\Bundle\GraphQLClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('idci_graphql_client');
        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('cache_enabled')->defaultFalse()->end()
                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('http_client')->isRequired()->cannotBeEmpty()->end()
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
