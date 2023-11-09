IDCI GraphQL Client Bundle
==========================

This symfony 4 bundle provide help with GraphQL api with php thanks to a client, a query builder and a cache management.

Installation
------------

With composer:

```shell
$ composer require idci/graphql-client-bundle
```

Basic configuration
-------------------

Define new GuzzleHttp client(s):
```yaml
eight_points_guzzle:
    clients:
        my_guzzle_client_one:
            base_url: 'http://one.example.com/' # will target http://one.example.com/graphql/ as entrypoint
        my_guzzle_client_two:
            base_url: 'http://two.example.com/'  # will target http://two.example.com/graphql/ as entrypoint
```

Define new GraphQL client(s):

```yaml
idci_graphql_client:
    clients:
        my_client_one:
            http_client: 'eight_points_guzzle.client.my_guzzle_client_one'
        my_client_two:
            http_client: 'eight_points_guzzle.client.my_guzzle_client_two'
```

```yaml
framework:
    cache:
        my_provider_one: "%env(resolve:MY_CACHE_DSN)%"
        pools:
            cache.my_cache_one:
                adapter: app.cache.my_adapter_one.my_cache_one
                default_lifetime: 600
                public: true
            cache.my_cache_two:
                adapter: app.cache.my_adapter_two.my_cache_two
                default_lifetime: 3600
                public: true
```

Then you can call it by using the registry, for example:

```php
<?php

namespace App\Controller;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientRegistryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function homeAction(GraphQLApiClientRegistryInterface $graphQlApiClientRegistry)
    {
        $firstClient = $graphQlApiClientRegistry->get('my_client_one');
        $secondClient = $graphQlApiClientRegistry->get('my_client_two');
        // ...
    }
}
```

Simple Query builder
--------------------

The client use a query builder which simplify the formatting of the graphql query.

```php
<?php

class GraphQLApiClient
{
    public function buildQuery($action, array $requestedFields): GraphQLQuery;

    public function buildMutation($action, array $requestedFields): GraphQLQuery;
}
```

Then the GraphQLQuery object can be use to retrieve the builded GraphQL query in string format

```php
<?php

$queryString = $query->getGraphQlQuery();
echo $queryString;
```

Or to retrieve the results of the query

```php
<?php

$results = $query->getResults();
```

### Examples

#### Fields
```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildQuery(
    'child',
    [
        'name',
        'age',
        'parents' => [
            'name',
        ],
    ]
)->getGraphQlQuery();
```

will generate

```
{
  child {
    name
    age
    parents {
      name
    }
  }
}
```

#### Query arguments
```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildQuery(
    [
        'child' => [
            'name' => 'searchedName'
        ]
    ],
    [
        'name',
        'age',
        'parents' => [
            'name',
        ],
    ]
)->getGraphQlQuery();
```

will generate

```
{
  child(name: "searchedName") {
    name
    age
    parents {
      name
    }
  }
}
```

#### Sub-query arguments
```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildQuery(
    'child',
    [
        'name',
        'age',
        'parents' => [
            '_parameters' => [ // reserved argument
                'name' => 'searchedName'
            ],
            'name',
            'cars' => [
                'color'
            ]
        ],
    ]
)->getGraphQlQuery();
```

will generate

```
{
  child {
    name
    age
    parents(name: "searchedName") {
      name
      cars {
        color
      }
    }
  }
}
```

#### Fragments
```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildQuery(
    'child',
    [
        'name',
        'age',
        'toys' => [
            '_fragments' => [
                'Robot' => [
                    'name',
                    'sensors',
                ],
                'Car' => [
                    'name',
                    'color',
                ],
            ],
        ],
    ]
)->getGraphQlQuery();
```

will generate

```
{
  child {
    name
    age
    toys {
      ... on Robot {
        name
        sensors
      }
      ... on Car {
        name
        color
      }
    }
  }
}
```

#### Aliases

```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildQuery(
    'child',
    [
        'name',
        'age',
        'toys' => [
            '_alias' => 'green_toys',
            '_parameters' => [ // reserved argument
                'color' => 'green'
            ],
            'name',
            'color',
        ],
        'toys' => [
            '_alias' => 'blue_toys',
            '_parameters' => [ // reserved argument
                'color' => 'blue'
            ],
            'name',
            'color',
        ],
    ]
)->getGraphQlQuery();
```

will generate

```
{
  child {
    name
    age
    green_toys: toys(color: "green") {
      name
      color
    }
    blue_toys: toys(color: "blue") {
      name
      color
    }
  }
}
```

#### Mutations

```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildMutation(
    [
        'child' => [
            'age' => 6
        ]
    ],
    [
        'name',
        'age',
    ]
)->getGraphQlQuery();
```

will generate

```
mutation {
  child(age: 6) {
    name
    age
  }
}
```

Fluent Query builder
--------------------

You can also use an alternative version of the query builder with a fluent interface (inspired by doctrine query builder).

```php
<?php

$qb = $graphQlApiClientRegistry->get('my_client')->createQueryBuilder();

$qb
    ->setType('mutation')
    ->setAction('child')
    ->addArgument('age', 6)
    ->addRequestedFields('name')
    ->addRequestedFields('age')
    ->addRequestedFields('toys', [
        '_fragments' => [
            'Robot' => [
                'name',
                'sensors',
            ],
            'Car' => [
                'name',
                'color',
            ],
        ],
    ])
;

$qb->getQuery()->getResults();

?>
```

Will generate

```
mutation {
  child(age: 6) {
    name
    age
    toys {
      ... on Robot {
        name
        sensors
      }
      ... on Car {
        name
        color
      }
    }
  }
}
```

Cache
-----

Install [symfony/cache](https://packagist.org/packages/symfony/cache):

```shell
$ composer require symfony/cache
```

Create new cache adapter provider(s) in your ```config/packages/cache.yaml``` ([official docs](https://symfony.com/doc/current/components/cache))

```yaml
framework:
  cache:
    # Redis
    app: cache.adapter.redis
    default_redis_provider: "%env(resolve:REDIS_DSN)%"

    pools:
        cache.my_first_adapter: ~
        cache.my_second_adapter: ~

```

Update your configuration in ```config/packages/idci_graphql_client.yaml```

```yaml
idci_graphql_client:
    cache_enabled: true
    clients:
        my_client_one:
            http_client: 'eight_points_guzzle.client.my_guzzle_client_one'
            cache: 'cache.my_first_adapter'
        my_client_two:
            http_client: 'eight_points_guzzle.client.my_guzzle_client_two'
            cache: 'cache.my_second_adapter'
```

Now when your client execute a query the result will be inserted or retrieved from your cache provider

You can also activate/deactivate cache for a specific environment by adding a new yaml configuration file in ```config/packages/dev/``` or ```config/packages/test/```, for example:

```yaml
# config/packages/dev/idci_graphql_client.yaml
idci_graphql_client:
    cache_enabled: false
```

Liip Monitor Bundle
-------------------

Install LiipMonitorBundle

```sh
$ composer req liip/monitor-bundle
```

Enable the checker in your service configuration

```yaml
IDCI\Bundle\GraphQLClientBundle\Check\GraphQLApiCheck:
    autoconfigure: false
    arguments:
        $client: '@eight_points_guzzle.client.graphql_api_entrypoint'
        $name: 'My API Name'
    tags:
        - { name: liip_monitor.check, group: api }

```

Command
-------

You can select which cache you want purged by using

```shell
$ php bin/console cache:graphql:clear
```
