<?php

namespace IDCI\Bundle\GraphQLClientBundle\Handler;

interface CacheHandlerInterface
{
    public function generateHash(string $data): string;

    public function isCached($key): bool;

    public function set($key, $value, $ttl = 3600);

    public function get($key);

    public function remove($key);

    public function purge();
}
