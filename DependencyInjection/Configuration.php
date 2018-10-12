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
                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('http_client')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
