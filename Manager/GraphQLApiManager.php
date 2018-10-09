<?php

namespace IDCI\Bundle\GraphQLClientBundle\Manager;

use GuzzleHttp\ClientInterface;

class GraphQLApiManager
{
    public function __construct(GraphQLCacheManager $cache, ClientInterface $client, string $apiHost)
    {
        $this->cache = $cache;
        $this->client = $client;
        $this->apiHost = $apiHost;
    }

    public function getApiUrl()
    {
        return sprintf('http://%s/graphql/', $this->apiHost);
    }

    public function query(string $graphQlQuery)
    {
        $graphQlQueryHash = sha1($graphQlQuery);

        if ($this->cache->isCached($graphQlQueryHash)) {
            return json_decode($this->cache->get($graphQlQueryHash), true);
        }

        $response = $this->client->post($this->getApiUrl(), [
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
