<?php

namespace IDCI\Bundle\GraphQLClientBundle\Handler;

use Predis\Client;

class RedisCacheHandler implements CacheHandlerInterface
{
    /**
     * @var Client
     */
    private $redisClient;

    public function __construct(string $redisHost = 'redis.maier.docker', string $alias = 'product') // host & alias configurable
    {
        $this->client = new Client(sprintf('tcp://%s?alias=%s', $redisHost, $redisAlias));
    }

    public function generateHash(string $data): string
    {
        return hash('sha1', $data); // algo configurable
    }

    public function isCached($key)
    {
        return 0 < $this->client->executeRaw(['TTL', $key]);
    }

    public function set($key, $value, $ttl = 3600) // ttl configurable
    {
        $this->client->executeRaw(['SET', $key, $value]);
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
