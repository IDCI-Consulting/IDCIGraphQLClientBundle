<?php

namespace IDCI\Bundle\GraphQLClientBundle;

use IDCI\Bundle\GraphQLClientBundle\DependencyInjection\Compiler\GraphQLApiClientCompilerPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class IDCIGraphQLClientBundle extends AbstractBundle
{
    protected string $extensionAlias = 'idci_graphql_client';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('cache_enabled')->defaultFalse()->end()
                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('http_client')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('cache')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        $builder->setParameter('idci_graphql_client.cache_enabled', $config['cache_enabled']);
        $builder->setParameter('idci_graphql_client.clients', $config['clients']);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GraphQLApiClientCompilerPass());
    }
}
