<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQuery;
use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQueryBuilder;
use Symfony\Component\Cache\Adapter\AdapterInterface;

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
            if (!interface_exists(AdapterInterface::class)) {
                throw new \RuntimeException('IDCIGraphQLClient cache requires "symfony/cache" package');
            }

            if (!$cache instanceof AdapterInterface) {
                throw new \UnexpectedValueException(
                    sprintf('The client\'s cache adapter must implement %s.', AdapterInterface::class)
                );
            }

            $this->cache = $cache;
        }
    }

    public function createQueryBuilder(): GraphQLQueryBuilder
    {
        return new GraphQLQueryBuilder($this);
    }

    public function buildQuery($action, array $requestedFields): GraphQLQuery
    {
        return new GraphQLQuery(GraphQLQuery::QUERY_TYPE, $action, $requestedFields, $this);
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
            if (isset($result['errors'][0]['debugMessage'])) {
                throw new \UnexpectedValueException($result['errors'][0]['debugMessage']);
            }
            throw new \UnexpectedValueException($result['errors'][0]['message']);
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
