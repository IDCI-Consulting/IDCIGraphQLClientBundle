<?php

namespace IDCI\Bundle\GraphQLClientBundle\Query;

use GraphQL\Graph;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientInterface;

class GraphQLQuery
{
    /**
     * @var string|array
     */
    private $action;

    /**
     * @var array
     */
    private $actionParameters;

    /**
     * @var array
     */
    private $requestedFields;

    /**
     * @var string
     */
    private $query;

    /**
     * @var GraphQLApiClientInterface
     */
    private $client;

    public function __construct($action, array $requestedFields, GraphQLApiClientInterface $client)
    {
        if (!is_array($action) && !is_string($action)) {
            throw new \InvalidArgumentException('action parameter must be a string or an array');
        }

        $this->action = $action;
        $this->requestedFields = $requestedFields;
        $this->client = $client;

        if (is_array($action)) {
            $key = array_keys($action)[0];

            if (0 === $key) {
                throw new \InvalidArgumentException('Action parameters must be associative array');
            }

            $this->action = $key;
            $this->actionParameters = $action[$key];

            $graphQlQuery = new Graph($key, $action[$key]);
        } else {
            $graphQlQuery = new Graph($action);
        }

        array_walk($requestedFields, [$this, 'buildGraph'], $graphQlQuery);

        $this->query = $this->decodeGraphQlQuery($graphQlQuery);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getActionParameters(): array
    {
        return $this->actionParameters;
    }

    public function getRequestedFields(): array
    {
        return $this->requestedFields;
    }

    public function getGraphQLQuery(): string
    {
        return $this->query;
    }

    public function getResults($cache = true)
    {
        return $this->client->query($this, $cache);
    }

    private function decodeGraphQlQuery(string $graphQlQuery)
    {
        return preg_replace_callback('/\\\\u([a-f0-9]{4})/', function ($param) {
            return iconv('UCS-4LE', 'UTF-8', pack('V', hexdec(sprintf('U%s', $param[0]))));
        }, $graphQlQuery);
    }

    public function getHash()
    {
        return hash('sha1', $this->query);
    }

    private function buildGraph($field, $key, &$graphQlQuery)
    {
        if (is_array($field)) {
            if (array_key_exists('_parameters', $field)) {
                $graphQlQuery->$key($field['_parameters']);
                array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key($field['_parameters']));
            } elseif ('_parameters' !== $key) {
                $graphQlQuery->$key;
                array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key);
            }
        } else {
            $graphQlQuery->use($field);
        }
    }
}
