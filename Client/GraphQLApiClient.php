<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use GuzzleHttp\ClientInterface;
use IDCI\Bundle\GraphQLClientBundle\Handler\CacheHandlerInterface;
use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQuery;

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

    public function buildQuery($action, array $requestedFields): GraphQLQuery
    {
        return new GraphQLQuery($action, $requestedFields, $this);
    }

    public function query(GraphQLQuery $graphQlQuery, bool $cache = true): array
    {
        $graphQlQueryHash = $this->cache->generateHash($graphQlQuery->getGraphQlQuery());

        if ($cache && $this->cache->isCached($graphQlQueryHash)) {
            return json_decode($this->cache->get($graphQlQueryHash), true);
        }

        $response = $this->httpClient->request('POST', '/graphql/', [
            'query' => [
                'query' => $graphQlQuery->getGraphQlQuery(),
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['data']) || (isset($result['errors']) && 0 < count($result['errors']))) {
            if (isset($result['errors'][0]['debugMessage'])) {
                throw new \UnexpectedValueException($result['errors'][0]['debugMessage']);
            }

            throw new \UnexpectedValueException($result['errors'][0]['message']);
        }

        $this->cache->set($graphQlQueryHash, json_encode($result['data'][$graphQlQuery->getAction()]));

        return $result['data'][$graphQlQuery->getAction()];
    }
}
