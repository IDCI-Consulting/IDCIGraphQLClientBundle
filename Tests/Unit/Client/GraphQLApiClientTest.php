<?php

namespace IDCI\Bundle\GraphQLClientBundle\Test\Unit\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

class GraphQLApiClientTest extends TestCase
{
    public function setUp()
    {
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->item = new CacheItem();

        $this->cache
            ->method('getItem')
            ->willReturn($this->item)
        ;

        $this->cachedResult = [
            'object' => [
                'id' => 1,
                'cached' => true,
            ],
        ];

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
    public function testBuildQueryWithInvalidActionType()
    {
        $this->graphQlApiClient->buildQuery(
            1000,
            $this->notCachedQuery['requestedFields']
        );
    }

    public function testBuildQuery()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQuery(
                $this->notCachedQuery['action'],
                $this->notCachedQuery['requestedFields']
            )->getGraphQLQuery(),
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

    public function testBuildQueryWithParameters()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQuery(
                [
                    $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
                ],
                $this->notCachedQuery['requestedFields']
            )->getGraphQLQuery(),
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

    public function testBuildQueryWithSubQueryParameters()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQuery(
                $this->notCachedQuery['action'],
                $this->notCachedQuery['requestedFieldsWithSubParameters']
            )->getGraphQLQuery(),
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

    public function testBuildQueryIfEncodedChar()
    {
        $this->assertEquals(
            $this->graphQlApiClient->buildQuery(
                'test\u00e9',
                $this->notCachedQuery['requestedFields']
            )->getGraphQLQuery(),
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
            $this->graphQlApiClient->buildQuery(
                1000,
                $this->notCachedQuery['requestedFields']
            )
        );
    }

    public function testQueryIfAlreadyInCache()
    {
        $graphQlQuery = $this->graphQlApiClient->buildQuery(
            [
                $this->cachedQuery['action'] => $this->cachedQuery['parameters'],
            ],
            $this->cachedQuery['requestedFields']
        );

        $this->item->set($this->cachedResult);

        $this->cache
            ->method('hasItem')
            ->willReturn(true)
        ;

        $this->cache
            ->method('getItem')
            ->willReturn($this->item)
        ;

        $result = $this->graphQlApiClient->query($graphQlQuery);

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
            $this->graphQlApiClient->buildQuery(
                [
                    $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
                ],
                $this->notCachedQuery['requestedFields']
            )
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
            $this->graphQlApiClient->buildQuery(
                [
                    $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
                ],
                $this->notCachedQuery['requestedFields']
            )
        );
    }

    public function testQueryIfNotInCache()
    {
        $this->httpClient->method('request')->willReturn($this->httpClientSuccessfulResponse);
        $this->cache
            ->method('hasItem')
            ->willReturn(false)
        ;

        $result = $this->graphQlApiClient->query(
            $this->graphQlApiClient->buildQuery(
                [
                    $this->notCachedQuery['action'] => $this->notCachedQuery['parameters'],
                ],
                $this->notCachedQuery['requestedFields']
            )
        );

        $this->assertEquals($this->notCachedResult['testGetObjects'], $result);
    }
}
