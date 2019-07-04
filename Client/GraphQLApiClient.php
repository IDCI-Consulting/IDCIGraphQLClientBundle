<?php

namespace IDCI\Bundle\GraphQLClientBundle\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQuery;
use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQueryBuilder;
use Psr\Log\LoggerInterface;
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

    public function __construct(LoggerInterface $logger, ClientInterface $httpClient, $cache = null, ?int $cacheTTL = 3600)
    {
        $this->httpClient = $httpClient;
        $this->cacheTTL = $cacheTTL;
        $this->logger = $logger;

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

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function createQueryBuilder(): GraphQLQueryBuilder
    {
        return new GraphQLQueryBuilder($this);
    }

    public function buildQuery($action, array $requestedFields): GraphQLQuery
    {
        return new GraphQLQuery(GraphQLQuery::QUERY_TYPE, $action, $requestedFields, $this);
    }

    public function buildMutation($action, array $requestedFields): GraphQLQuery
    {
        return new GraphQLQuery(GraphQLQuery::MUTATION_TYPE, $action, $requestedFields, $this);
    }

    public function query(GraphQLQuery $graphQlQuery, bool $cache = true): ?array
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
        } catch (RequestException $e) {
            $this->logger->error('Error in GraphQLApiClient', [
                'query' => $graphQlQuery->getGraphQlQuery(),
                'error' => $e->getMessage(),
                'response' => $e->getResponse(),
                'body' => null !== $e->getResponse() ? (string) $e->getResponse()->getBody() : null,
            ]);

            throw $e;
        }

        $result = json_decode($response->getBody(), true);

        if ($cache && null !== $this->cache) {
            $item = $this->cache->getItem($graphQlQueryHash);

            $item->set($result['data'][$graphQlQuery->getAction()]);
            $item->expiresAfter($this->cacheTTL);

            $this->cache->save($item);
        }

        return $result['data'][$graphQlQuery->getAction()];
    }
}
