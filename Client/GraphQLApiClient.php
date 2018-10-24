<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use Cache\Hierarchy\HierarchicalPoolInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
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

    public function __construct(ClientInterface $httpClient, $cache = null, ?int $cacheTTL = 3600)
    {
        $this->httpClient = $httpClient;
        $this->cacheTTL = $cacheTTL;

        $this->setCache($cache);
    }

    private function setCache($cache)
    {
        if (null !== $cache) {
            if (!interface_exists(HierarchicalPoolInterface::class)) {
                throw new \RuntimeException('IDCIGraphQLClient cache requires "cache/adapter-bundle" package');
            }

            if (!$cache instanceof HierarchicalPoolInterface) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'GraphQL client must implement %s, %s given',
                        HierarchicalPoolInterface::class,
                        get_class($cache)
                    )
                );
            }

            $this->cache = $cache;
        }
    }

    public function buildQuery($action, array $requestedFields): GraphQLQuery
    {
        return new GraphQLQuery($action, $requestedFields, $this);
    }

    public function query(GraphQLQuery $graphQlQuery, bool $cache = true): array
    {
        $graphQlQueryHash = $graphQlQuery->getHash();
        if ($cache && null !== $this->cache && $this->cache->hasItem($graphQlQueryHash)) {
            return $this->cache->getItem($graphQlQueryHash)->get();
        }

        try {
            $response = $this->httpClient->request('POST', '', [
                'headers' => [
                    'Accept-Encoding' => 'gzip',
                ],
                'form_params' => [
                    'query' => $graphQlQuery->getGraphQlQuery(),
                ],
            ]);
        } catch (TransferException $e) {
            throw new \RuntimeException(sprintf('Network Error: %s', $e->getMessage()), 0, $e);
        }

        $result = json_decode($response->getBody(), true);

        if (!isset($result['data']) || (isset($result['errors'][0]))) {
            throw new \UnexpectedValueException(json_encode($result['errors']));
        }

        if ($cache && null !== $this->cache) {
            $item = $this->cache->getItem($graphQlQueryHash);

            $item->set($result['data'][$graphQlQuery->getAction()]);
            $item->expiresAfter($this->cacheTTL);

            $this->cache->save($item);
        }

        return $result['data'][$graphQlQuery->getAction()];
    }
}
