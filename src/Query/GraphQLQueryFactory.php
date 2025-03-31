<?php

namespace IDCI\Bundle\GraphQLClientBundle\Query;

class GraphQLQueryFactory
{
    public static function fromString(string $query): GraphQLQuery
    {
        $class = new \ReflectionClass(GraphQLQuery::class);

        $graphQLQuery = $class->newInstanceWithoutConstructor();
        $graphQLQuery->setGraphQLQuery($query);

        preg_match('/^{?(.*)\(/', $query, $matches);
        $graphQLQuery->setAction(trim($matches[1]));

        return $graphQLQuery;
    }
}
