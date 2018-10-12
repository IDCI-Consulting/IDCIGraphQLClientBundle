<?php

namespace IDCI\Bundle\GraphQLClientBundle\Handler;

use Predis\Client;

class RedisCacheHandler implements CacheHandlerInterface
{
    /**
     * @var Client
     */
    private $redisClient;

    public function __construct(string $host = 'redis.maier.docker', string $alias = 'product') // host & alias configurable
    {
        $this->redisClient = new Client(sprintf('tcp://%s?alias=%s', $host, $alias));
    }

    public function generateHash(string $data): string
    {
        return hash('sha1', $data); // algo configurable
    }

    public function isCached($key): bool
    {
        return 0 < $this->redisClient->executeRaw(['TTL', $key]);
    }

    public function set($key, $value, $ttl = 3600) // ttl configurable
    {
        $this->redisClient->executeRaw(['SET', $key, $value]);
        $this->redisClient->executeRaw(['EXPIRE', $key, $ttl]);
    }

    public function get($key)
    {
        return $this->redisClient->executeRaw(['GET', $key]);
    }

    public function remove($key)
    {
        $this->redisClient->executeRaw(['DEL', $key]);
    }

    public function purge()
    {
        $this->redisClient->executeRaw(['FLUSHALL']);
    }
}
