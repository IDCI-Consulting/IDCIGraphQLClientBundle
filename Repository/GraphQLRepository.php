<?php

namespace IDCI\Bundle\GraphQLClientBundle\Repository;

use App\Manager\GraphQLApiManager;
use GraphQL\Graph;

class GraphQLRepository
{
    /**
     * @var GraphQLApiManager
     */
    private $graphQl;

    public function __construct(GraphQLApiManager $graphQl)
    {
        $this->graphQl = $graphQl;
    }

    public function __call(string $action, $arguments)
    {
        $graphQlQuery = new Graph(
            $action,
            $this->buildParameters($arguments[0], $arguments[1])
        );

        array_walk($arguments[2], [$this, 'buildGraph'], $graphQlQuery);

        $queryResult = $this->graphQl->query($graphQlQuery)[$action];

        return isset($queryResult['elements']) ? $queryResult['elements'] : $queryResult;
    }

    private function buildParameters(array $parameters, array $filters)
    {
        $builtParameters = [];

        foreach ($filters as $filter) {
            if (isset($parameters[$filter]) && !empty($parameters[$filter])) {
                $builtParameters[$filter] = $parameters[$filter];
            }
        }

        return $builtParameters;
    }

    private function decodeGraphQLQuery(string $graphQlQuery)
    {
        return preg_replace_callback('/\\\\u([a-f0-9]{4})/', function ($param) {
            return iconv('UCS-4LE', 'UTF-8', pack('V', hexdec(sprintf('U%s', $param[0]))));
        }, $graphQlQuery);
    }

    public function on(string $target)
    {
        $this->target = $target;

        return $this;
    }

    public function findAll(array $fields)
    {
        if (!isset($this->target)) {
            throw new \Exception('No target defined, use on() method to set one');
        }

        $graphQlQuery = new Graph($this->target);

        $graphQlQuery
            ->use('totalCount')
            ->elements
        ;

        array_walk($fields, [$this, 'buildGraph'], $graphQlQuery->elements);

        return $this->graphQl->query($this->decodeGraphQLQuery($graphQlQuery))[$this->target];
    }

    public function findAllBy(array $parameters, array $filters, array $fields)
    {
        if (!isset($this->target)) {
            throw new \Exception('No target defined, use on() method to set one');
        }

        $graphQlQuery = new Graph($this->target);
        $graphQlQuery
            ->use('totalCount')
        ;

        array_walk($fields, [$this, 'buildGraph'], $graphQlQuery->elements($this->buildParameters($parameters, $filters)));

        return $this->graphQl->query($this->decodeGraphQLQuery($graphQlQuery))[$this->target];
    }

    public function findBy(array $parameters, array $filters, array $fields)
    {
        if (!isset($this->target)) {
            throw new \Exception('No target defined, use on() method to set one');
        }

        $action = sprintf('get%s', ucfirst($this->target));

        $graphQlQuery = new Graph(
            $action,
            $this->buildParameters($parameters, $filters)
        );

        array_walk($fields, [$this, 'buildGraph'], $graphQlQuery);

        return $this->graphQl->query($this->decodeGraphQLQuery($graphQlQuery))[$action];
    }

    public function findOneBy(array $parameters, array $filters, array $fields)
    {
        if (!isset($this->target)) {
            throw new \Exception('No target defined, use on() method to set one');
        }

        $action = substr($this->target, 0, strlen($this->target) - 1);

        $graphQlQuery = new Graph($action, $this->buildParameters($parameters, $filters));

        array_walk($fields, [$this, 'buildGraph'], $graphQlQuery);

        return $this->graphQl->query($this->decodeGraphQLQuery($graphQlQuery))[$action][0];
    }

    public function countBy(array $parameters, array $filters)
    {
        if (!isset($this->target)) {
            throw new \Exception('No target defined, use on() method to set one');
        }

        $action = sprintf('getNumberOf%s', ucfirst($this->target));

        $graphQlQuery = new Graph(
            $action,
            $this->buildParameters($parameters, $filters)
        );

        $graphQlQuery->use('totalCount');

        return $this->graphQl->query($this->decodeGraphQLQuery($graphQlQuery))[$action]['totalCount'];
    }

    public function buildGraph($field, $key, &$graphQlQuery)
    {
        if (is_array($field)) {
            $graphQlQuery->$key;
            array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key);
        } else {
            $graphQlQuery->use($field);
        }
    }
}
