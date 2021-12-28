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
    const DEFAULT_CACHE_TTL = 3600;

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

    public function __construct(LoggerInterface $logger, ClientInterface $httpClient, $cache = null, ?int $cacheTTL = self::DEFAULT_CACHE_TTL)
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
                throw new \UnexpectedValueException(sprintf('The client\'s cache adapter must implement %s.', AdapterInterface::class));
            }

            $this->cache = $cache;
        }
    }

    public function getCacheTTL(): int
    {
        return $this->cacheTTL;
    }

    public function setCacheTTL(int $cacheTTL): self
    {
        $this->cacheTTL = $cacheTTL;

        return $this;
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
            $response = $this->httpClient->request('POST', $graphQlQuery->hasEndpoint() ? $graphQlQuery->getEndpoint() : '', array_merge([
                'headers' => [
                    'Accept-Encoding' => 'gzip',
                ],
            ], $this->buildRequestParams($graphQlQuery)));
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

        if (null === $result || !isset($result['data']) || null === $result['data'][$graphQlQuery->getAction()]) {
            $this->logger->warning('Warning in GraphQLApiClient, no value returned. Probably an error has been encountred.', [
                'query' => $graphQlQuery->getGraphQlQuery(),
                'result' => $result,
            ]);
        }

        if (isset($result['errors']) && !empty($result['errors'])) {
            throw new \Exception(sprintf('Error occuring during the execution of GraphQL query "%s". Result: %s', $graphQlQuery->getGraphQlQuery(), json_encode($result)));
        }

        if ($cache && null !== $this->cache) {
            $item = $this->cache->getItem($graphQlQueryHash);

            $item->set($result['data'][$graphQlQuery->getAction()]);
            $item->expiresAfter($this->cacheTTL);

            $this->cache->save($item);
        }

        return $result['data'][$graphQlQuery->getAction()];
    }

    private function buildRequestParams(GraphQLQuery $graphQlQuery): array
    {
        if (!$graphQlQuery->hasFiles()) {
            return [
                'form_params' => [
                    'query' => $graphQlQuery->getGraphQlQuery(),
                ],
            ];
        }

        $formParams = [
            'multipart' => [
                [
                    'name' => 'query',
                    'contents' => $graphQlQuery->getGraphQlQuery(),
                ],
            ],
        ];

        foreach ($graphQlQuery->getFiles() as $file) {
            $formParams['multipart'][] = [
                'name' => 'files[]',
                'contents' => fopen((string) $file, 'r'),
                'headers' => ['Content-Type' => $file->getMimeType()],
            ];
        }

        return $formParams;
    }
}
