<?php

namespace IDCI\Bundle\GraphQLClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = (bool) $debug;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('redis');
        $rootNode
            ->children()
                ->scalarNode('host')->end()
                ->scalarNode('alias')->end()
                ->scalarNode('default_ttl')->end()
            ->end()
        ;

        dump('test');

        return $treeBuilder;
    }
}
