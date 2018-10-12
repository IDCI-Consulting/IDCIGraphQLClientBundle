<?php

namespace IDCI\Bundle\GraphQLClientBundle\DependencyInjection\Compiler;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClient;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientRegistry;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GraphQLApiClientCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registryDefinition = $container->getDefinition(GraphQLApiClientRegistry::class);

        $graphQlClients = $container->getParameter('idci_graphql_client.clients');

        foreach ($graphQlClients as $alias => $configuration) {
            $serviceDefinition = new ChildDefinition(GraphQLApiClient::class);
            $serviceDefinition->setAbstract(false);
            $serviceDefinition->replaceArgument(0, $container->getDefinition($configuration['http_client']));

            $serviceName = sprintf('idci_graphql_client.clients.%s', $alias);
            $container->setDefinition($serviceName, $serviceDefinition);

            $registryDefinition->addMethodCall('set', [$alias, new Reference($serviceName)]);
        }

        $taggedServices = $container->findTaggedServiceIds('idci_graphql_client.api_client');
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $registryDefinition->addMethodCall(
                    'set',
                    [
                        $attributes['alias'],
                        new Reference($id),
                    ]
                );
            }
        }
    }
}
