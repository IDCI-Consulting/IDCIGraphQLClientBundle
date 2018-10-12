<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

class GraphQLApiClientRegistry implements GraphQLApiClientRegistryInterface
{
    private $graphQlApiClients = [];

    public function has(string $alias): bool
    {
        return isset($this->graphQlApiClients[$alias]);
    }

    public function set(string $alias, GraphQLApiClientInterface $graphQlApiClient): GraphQLApiClientRegistryInterface
    {
        $this->graphQlApiClients[$alias] = $graphQlApiClient;

        return $this;
    }

    public function get(string $alias): GraphQLApiClientInterface
    {
        if (!isset($this->graphQlApiClients[$alias])) {
            throw new \UnexpectedValueException(sprintf('Could not load graphql api client with alias %s', $alias));
        }

        return $this->graphQlApiClients[$alias];
    }

    public function getAll(): array
    {
        return $this->graphQlApiClients;
    }
}
