<?php

namespace IDCI\Bundle\GraphQLClientBundle\Test\Unit\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClient;
use IDCI\Bundle\GraphQLClientBundle\Handler\CacheHandlerInterface;
use PHPUnit\Framework\TestCase;

class GraphQLApiClientTest extends TestCase
{
    public function setUp()
    {
        $this->cache = $this->createMock(CacheHandlerInterface::class);

        $this->cache->method('generateHash')->will($this->returnCallback(function ($graphQlQuery) {
            return sha1($graphQlQuery);
        }));

        $this->cache->method('isCached')->will($this->returnCallback(function ($hashGraphQlQuery) {
            if ('92393a1e3ea51e5dd1fe80b66930d6e8608daa23' === $hashGraphQlQuery) {
                return false;
            }

            return true;
        }));

        $this->cachedResult = [
            'object' => [
                'id' => 1,
                'cached' => true,
            ],
        ];

        $this->cache->method('get')->willReturn(json_encode($this->cachedResult));

        $this->cachedQuery = [
            'action' => 'testGetObjects',
            'requestedFields' => [
                'objects' => [
                    'id',
                ],
            ],
            'parameters' => [
                'cached' => 'true',
            ],
        ];

        $this->notCachedQuery = [
            'action' => 'testGetObjects',
            'requestedFields' => [
                'objects' => [
                    'id',
                ],
            ],
            'parameters' => [
                'cached' => 'false',
            ],
        ];

        $this->httpClient = $this->getMockBuilder(ClientInterface::class)
            ->getMock()
        ;

        $this->notCachedResult = [
            'object' => [
                'id' => 1,
                'cached' => false,
            ],
        ];

        $this->httpClientSuccessfulResponse = new Response(
            200,
            ['content-type' => 'text/json'],
            json_encode(['data' => $this->notCachedResult]),
            1.1
        );

        $this->httpClientErrorResponse = new Response(
            200,
            ['content-type' => 'text/json'],
            json_encode(['errors' => [[
                'message' => 'there is an error',
            ]]]),
            1.1
        );

        $this->graphQlApiClient = new GraphQLApiClient($this->httpClient, $this->cache);
    }

    public function testBuildQueryString()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQueryString(
                $this->notCachedQuery['action'],
                $this->notCachedQuery['requestedFields'],
                $this->notCachedQuery['parameters']
            ),
            <<<EOT
{
  testGetObjects(cached: "false") {
    objects {
      id
    }
  }
}
EOT
        );
    }

    public function testBuildQueryStringIfEncodedChar()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQueryString(
                'test\u00e9',
                $this->notCachedQuery['requestedFields'],
                $this->notCachedQuery['parameters']
            ),
            <<<EOT
{
  testé(cached: "false") {
    objects {
      id
    }
  }
}
EOT
        );
    }

    public function testQueryIfAlreadyInCache()
    {
        $result = $this->graphQlApiClient->query(
            $this->cachedQuery['action'],
            $this->cachedQuery['requestedFields'],
            $this->cachedQuery['parameters']
        );

        $this->assertEquals($this->cachedResult, $result);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testQueryWithNoDataReturned()
    {
        $this->httpClient->method('request')->willReturn($this->httpClientErrorResponse);

        $result = $this->graphQlApiClient->query(
            $this->notCachedQuery['action'],
            $this->notCachedQuery['requestedFields'],
            $this->notCachedQuery['parameters']
        );
    }

    public function testQueryIfNotInCache()
    {
        $this->httpClient->method('request')->willReturn($this->httpClientSuccessfulResponse);

        $result = $this->graphQlApiClient->query(
            $this->notCachedQuery['action'],
            $this->notCachedQuery['requestedFields'],
            $this->notCachedQuery['parameters']
        );

        $this->assertEquals($this->notCachedResult, $result);
    }
}
