<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use Cache\Namespaced\NamespacedCachePool;
use GuzzleHttp\ClientInterface;
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

    /**
     * @var int
     */
    private $cacheTTL;

    public function __construct(ClientInterface $httpClient, ?NamespacedCachePool $cache, ?int $cacheTTL = 3600)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->cacheTTL = $cacheTTL;
    }

    public function buildQuery($action, array $requestedFields): GraphQLQuery
    {
        return new GraphQLQuery($action, $requestedFields, $this);
    }

    public function query(GraphQLQuery $graphQlQuery, bool $cache = true): array
    {
        $graphQlQueryHash = $graphQlQuery->hash();
        if ($cache && null !== $this->cache && $this->cache->hasItem($graphQlQueryHash)) {
            return $this->cache->getItem($graphQlQueryHash)->get();
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

        if ($cache && null !== $this->cache) {
            $item = $this->cache->getItem($graphQlQuery->hash());

            $item->set($result['data'][$graphQlQuery->getAction()]);
            $item->expiresAfter($this->cacheTTL);

            $this->cache->save($item);
        }

        return $result['data'][$graphQlQuery->getAction()];
    }
}
