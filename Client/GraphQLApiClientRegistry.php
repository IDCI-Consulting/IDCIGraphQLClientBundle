<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

class GraphQLApiClientRegistry implements GraphQLApiClientRegistryInterface
{
    private $graphQlApiClients = [];

    public function has(string $alias): boolean
    {
        return isset($this->graphQlApiClients[$alias]);
    }

    public function set(string $alias, GraphQLApiClientInterface $graphQlApiClient): GraphQLApiClientRegistryInterface
    {
        $this->graphQlApiClients[$alias] = $graphQlApiClient;

        return $this;
    }

    public function get(string $alias): GraphQLApiClientRegistryInterface
    {
        if (!is_string($alias)) {
            throw new \InvalidArgumentException(sprintf('GraphQL api client alias is not a string (value: %s)', $alias));
        }

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
