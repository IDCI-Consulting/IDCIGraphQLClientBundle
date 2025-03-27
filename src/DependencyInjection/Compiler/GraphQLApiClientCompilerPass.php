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
    public function process(ContainerBuilder $container): void
    {
        $registryDefinition = $container->getDefinition(GraphQLApiClientRegistry::class);

        $servicesRootConfigurationName = 'idci_graphql_client.clients';
        $graphQlClients = $container->getParameter($servicesRootConfigurationName);

        foreach ($graphQlClients as $alias => $configuration) {
            $serviceDefinition = new ChildDefinition(GraphQLApiClient::class);
            $serviceDefinition->setAbstract(false);

            if (!isset($configuration['http_client'])) {
                throw new \InvalidArgumentException(sprintf('You must define a http client in graph ql client with alias %s under %s', $configuration['http_client'], $servicesRootConfigurationName));
            }

            $serviceDefinition->replaceArgument('$httpClient', $container->getDefinition($configuration['http_client']));

            if (isset($configuration['cache'])) {
                $serviceDefinition->replaceArgument('$cache', $container->getDefinition($configuration['cache']));
            }

            $serviceName = sprintf('%s.%s', $servicesRootConfigurationName, $alias);
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
