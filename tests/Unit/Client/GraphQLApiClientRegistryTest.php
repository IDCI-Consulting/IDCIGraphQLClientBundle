<?php

namespace IDCI\Bundle\GraphQLClientBundle\Test\Unit\Client;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientInterface;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientRegistry;
use PHPUnit\Framework\TestCase;

class GraphQLApiClientRegistryTest extends TestCase
{
    /**
     * @var GraphQLApiClientRegistry
     */
    private $graphQlApiClientRegistry;

    /**
     * @var GraphQLApiClientInterface
     */
    private $graphQlApiClient;

    public function setUp()
    {
        $this->graphQlApiClientRegistry = new GraphQLApiClientRegistry();

        $this->graphQlApiClient = $this->createMock(GraphQLApiClientInterface::class);
    }

    public function testSet()
    {
        $this->graphQlApiClientRegistry->set('dummy_alias', $this->graphQlApiClient);

        $this->assertTrue($this->graphQlApiClientRegistry->has('dummy_alias'));
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testGetIfNotExist()
    {
        $this->graphQlApiClientRegistry->get('unset_dummy_alias');
    }

    public function testGet()
    {
        $dummyGraphQlApiClient = $this->createMock(GraphQLApiClientInterface::class);
        $this->graphQlApiClientRegistry->set('dummy_alias', $dummyGraphQlApiClient);
        $this->graphQlApiClientRegistry->set('dummy_alias', $this->graphQlApiClient);

        $this->assertEquals($this->graphQlApiClient, $this->graphQlApiClientRegistry->get('dummy_alias'));
    }

    public function testGetAll()
    {
        $count = 5;

        for ($i = 0; $i < 5; ++$i) {
            $this->graphQlApiClientRegistry->set(sprintf('dummy_alias_%s', $i), $this->graphQlApiClient);
        }

        $this->assertEquals(5, count($this->graphQlApiClientRegistry->getAll()));
    }
}
