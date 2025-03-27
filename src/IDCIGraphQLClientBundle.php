<?php

namespace IDCI\Bundle\GraphQLClientBundle;

use IDCI\Bundle\GraphQLClientBundle\DependencyInjection\Compiler\GraphQLApiClientCompilerPass;
use IDCI\Bundle\GraphQLClientBundle\DependencyInjection\IDCIGraphQLClientExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class IDCIGraphQLClientBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GraphQLApiClientCompilerPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new IDCIGraphQLClientExtension();
    }
}
