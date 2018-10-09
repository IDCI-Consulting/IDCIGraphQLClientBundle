<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

class GraphQLApiClientInterface
{
    public function query(string $action, array $requestedFields, array $parameters = []): array;
}
