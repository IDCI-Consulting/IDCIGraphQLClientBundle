<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use GraphQL\Graph;
use GuzzleHttp\ClientInterface;
use IDCI\Bundle\GraphQLClientBundle\Handler\CacheHandlerInterface;

class GraphQLApiClient implements GraphQLApiClientInterface
{
    /**
     * @var RedisCacheHandler
     */
    private $cache;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    public function __construct(ClientInterface $httpClient, CacheHandlerInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    public function buildQueryString($action, array $requestedFields): string
    {
        if (is_array($action)) {
            $key = array_keys($action)[0];
            $graphQlQuery = new Graph($key, $action[$key]);
        } else {
            $graphQlQuery = new Graph($action);
        }

        array_walk($requestedFields, [$this, 'buildGraph'], $graphQlQuery);

        return $this->decodeGraphQLQuery($graphQlQuery);
    }

    public function query($action, array $requestedFields): array
    {
        if (!is_array($action) && !is_string($action)) {
            throw new \InvalidArgumentException('action parameter must be a string or an array');
        }

        $graphQlQuery = $this->buildQueryString($action, $requestedFields);

        $graphQlQueryHash = $this->cache->generateHash($graphQlQuery);

        if ($this->cache->isCached($graphQlQueryHash)) {
            return json_decode($this->cache->get($graphQlQueryHash), true);
        }

        $response = $this->httpClient->request('POST', '/graphql/', [
            'query' => [
                'query' => $graphQlQuery,
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['data']) || (isset($result['errors']) && 0 < count($result['errors']))) {
            if (isset($result['errors'][0]['debugMessage'])) {
                throw new \UnexpectedValueException($result['errors'][0]['debugMessage']);
            }

            throw new \UnexpectedValueException($result['errors'][0]['message']);
        }

        if (is_array($action)) {
            $action = array_keys($action)[0];
        }

        $this->cache->set($graphQlQueryHash, json_encode($result['data'][$action]));

        return $result['data'][$action];
    }

    private function decodeGraphQLQuery(string $graphQlQuery)
    {
        return preg_replace_callback('/\\\\u([a-f0-9]{4})/', function ($param) {
            return iconv('UCS-4LE', 'UTF-8', pack('V', hexdec(sprintf('U%s', $param[0]))));
        }, $graphQlQuery);
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
