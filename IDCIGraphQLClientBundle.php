<?php

namespace IDCI\Bundle\GraphQLClientBundle;

use IDCI\Bundle\GraphQLClientBundle\DependencyInjection\Compiler\GraphQLApiClientCompilerPass;
use IDCI\Bundle\GraphQLClientBundle\DependencyInjection\IDCIGraphQLClientExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class IDCIGraphQLClientBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new GraphQLApiClientCompilerPass());
    }

    public function getContainerExtension()
    {
        return new IDCIGraphQLClientExtension();
    }
}
