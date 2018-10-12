<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

interface GraphQLApiClientInterface
{
    public function query($action, array $requestedFields): array;
}
