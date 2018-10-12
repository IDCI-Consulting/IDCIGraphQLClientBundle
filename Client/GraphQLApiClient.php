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

    public function buildQueryString(string $action, array $requestedFields, array $parameters = []): string
    {
        $graphQlQuery = new Graph($action, $parameters);
        array_walk($requestedFields, [$this, 'buildGraph'], $graphQlQuery);

        return $this->decodeGraphQLQuery($graphQlQuery);
    }

    public function query(string $action, array $requestedFields, array $parameters = []): array
    {
        $graphQlQuery = $this->buildQueryString($action, $requestedFields, $parameters);
        $graphQlQueryHash = $this->cache->generateHash($graphQlQuery);

        if ($this->cache->isCached($graphQlQueryHash)) {
            return json_decode($this->cache->get($graphQlQueryHash), true);
        }

        $response = $this->httpClient->request('POST', [
            'query' => [
                'query' => $graphQlQuery,
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['data'])) {
            throw new \UnexpectedValueException($result['errors'][0]['message']);
        }

        $this->cache->set($graphQlQueryHash, json_encode($result['data']));

        return $result['data'];
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
            $graphQlQuery->$key;
            array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key);
        } else {
            $graphQlQuery->use($field);
        }
    }
}
