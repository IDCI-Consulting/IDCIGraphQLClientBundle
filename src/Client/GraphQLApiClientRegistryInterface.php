<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

interface GraphQLApiClientRegistryInterface
{
    public function has(string $alias): bool;

    public function set(string $alias, GraphQLApiClientInterface $graphQlApiClient): GraphQLApiClientRegistryInterface;

    public function get(string $alias): GraphQLApiClientInterface;

    public function getAll(): array;
}
