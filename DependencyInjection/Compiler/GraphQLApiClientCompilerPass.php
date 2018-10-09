<?php

namespace IDCI\Bundle\GraphQLClientBundle\DependencyInjection\Compiler;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientInterface;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentGatewayCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (
            !$container->hasDefinition(GraphQLApiClientRegistryInterface::class) ||
            !$container->hasDefinition(GraphQLApiClientInterface::class)
        ) {
            return;
        }

        $registryDefinition = $container->getDefinition(GraphQLApiClientRegistryInterface::class);

        $graphQlClients = $container->getParameter('idci_graphql_client.clients');

        foreach ($graphQlClients as $alias => $configuration) {
            $serviceDefinition = new DefinitionDecorator(GraphQLApiClientInterface::class);
            $serviceDefinition->setAbstract(false);
            $serviceDefinition->replaceArgument(0, new Reference($configuration['http_client']));

            $container->setDefinition(sprintf('idci_graphql_client.clients.%s', $alias));

            $registryDefinition->addMethodCall(
                'set',
                [
                    $alias,
                    new Reference(sprintf('idci_graphql_client.clients.%s', $alias)),
                ]
            );
        }

        $taggedServices = $container->findTaggedServiceIds('idci_graphql_client.clients');
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
