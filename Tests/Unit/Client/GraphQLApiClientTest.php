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
            'requestedFieldsWithSubParameters' => [
                'objects' => [
                    '_parameters' => [
                        'valid' => true,
                    ],
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
            'testGetObjects' => [
                'objects' => [
                    'id' => 1,
                    'cached' => false,
                ],
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

        $this->httpClientDebugResponse = new Response(
            200,
            ['content-type' => 'text/json'],
            json_encode(['errors' => [[
                'debugMessage' => 'this is a debug log',
            ]]]),
            1.1
        );

        $this->graphQlApiClient = new GraphQLApiClient($this->httpClient, $this->cache);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildQueryStringWithInvalidActionType()
    {
        $this->graphQlApiClient->buildQueryString(
            1000,
            $this->notCachedQuery['requestedFields']
        );
    }

    public function testBuildQueryString()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQueryString(
                $this->notCachedQuery['action'],
                $this->notCachedQuery['requestedFields']
            ),
            <<<EOT
{
  testGetObjects {
    objects {
      id
    }
  }
}
EOT
        );
    }

    public function testBuildQueryStringWithParameters()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQueryString(
                [
                    $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
                ],
                $this->notCachedQuery['requestedFields']
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

    public function testBuildQueryStringWithSubQueryParameters()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQueryString(
                $this->notCachedQuery['action'],
                $this->notCachedQuery['requestedFieldsWithSubParameters']
            ),
            <<<EOT
{
  testGetObjects {
    objects(valid: true) {
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
                $this->notCachedQuery['requestedFields']
            ),
            <<<EOT
{
  testé {
    objects {
      id
    }
  }
}
EOT
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testQueryWithInvalidActionType()
    {
        $this->graphQlApiClient->query(
            1000,
            $this->notCachedQuery['requestedFields']
        );
    }

    public function testQueryIfAlreadyInCache()
    {
        $result = $this->graphQlApiClient->query(
            [
                $this->cachedQuery['action'] => $this->cachedQuery['parameters'],
            ],
            $this->cachedQuery['requestedFields']
        );

        $this->assertEquals($this->cachedResult, $result);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage there is an error
     */
    public function testQueryWithNoDataReturnedButErrorMessage()
    {
        $this->httpClient->method('request')->willReturn($this->httpClientErrorResponse);

        $result = $this->graphQlApiClient->query(
            [
                $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
            ],
            $this->notCachedQuery['requestedFields']
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage this is a debug log
     */
    public function testQueryWithNoDataReturnedButDebugMessage()
    {
        $this->httpClient->method('request')->willReturn($this->httpClientDebugResponse);

        $result = $this->graphQlApiClient->query(
            [
                $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
            ],
            $this->notCachedQuery['requestedFields']
        );
    }

    public function testQueryIfNotInCache()
    {
        $this->httpClient->method('request')->willReturn($this->httpClientSuccessfulResponse);

        $result = $this->graphQlApiClient->query(
            [
                $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
            ],
            $this->notCachedQuery['requestedFields']
        );

        $this->assertEquals($this->notCachedResult['testGetObjects'], $result);
    }
}
