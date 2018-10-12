<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

interface GraphQLApiClientInterface
{
    public function query(string $action, array $requestedFields, array $parameters = []): array;
}
