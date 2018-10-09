<?php

namespace IDCI\Bundle\GraphQLClientBundle\Manager;

use Predis\Client;

class GraphQLCacheManager
{
    /**
     * @var Client
     */
    private $redisClient;

    public function __construct()
    {
        $this->client = new Client('tcp://redis.maier.docker?alias=redis'); // configurable
    }

    public function isCached($key)
    {
        return 0 < $this->client->executeRaw(['TTL', $key]);
    }

    public function set($key, $data, $ttl = 3600)
    {
        $this->client->executeRaw(['SET', $key, $data]);
        $this->client->executeRaw(['EXPIRE', $key, $ttl]);
    }

    public function get($key)
    {
        return $this->client->executeRaw(['GET', $key]);
    }

    public function remove($key)
    {
        $this->client->executeRaw(['DEL', $key]);
    }

    public function purge()
    {
        $this->client->executeRaw(['FLUSHALL']);
    }
}
