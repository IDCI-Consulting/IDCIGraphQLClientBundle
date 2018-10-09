<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use GuzzleHttp\ClientInterface;
use IDCI\Bundle\GraphQLClientBundle\Handler\CacheHandlerInterface;

class GraphQLApiClient
{
    /**
     * @var RedisCacheHandler
     */
    private $cache;

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $httpClient, CacheHandlerInterface $cache)
    {
        $this->cache = $cache;
        $this->httpClient = $httpClient;
    }

    public function query(string $action, array $requestedFields, array $parameters = []): array
    {
        $graphQlQueryHash = $this->cache->generateHash($graphQlQuery);

        if ($this->cache->isCached($graphQlQueryHash)) {
            return json_decode($this->cache->get($graphQlQueryHash), true);
        }

        $response = $this->httpClient->post([
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
}
