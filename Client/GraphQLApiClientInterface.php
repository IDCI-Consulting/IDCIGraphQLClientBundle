<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQuery;

interface GraphQLApiClientInterface
{
    public function query(GraphQLQuery $graphQlQuery, bool $cache = true): ?array;
}
