IDCI GraphQL Client Bundle
==========================

This symfony 4 bundle provide help with GraphQL api with php thanks to a client, a query builder and a cache management.

Pre-requisite
-------------

Install [eightpoints/guzzle-bundle](https://packagist.org/packages/eightpoints/guzzle-bundle):

```shell
$ composer require eightpoints/guzzle-bundle
```

Install [cache/adapter-bundle](https://packagist.org/packages/cache/adapter-bundle) (if you want to use cache):

```shell
$ composer require cache/adapter-bundle
```

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

Then you can call it by using the registry, for example:

```php
<?php

namespace App\Controller;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientRegistryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

Query builder
-------------

The client use a query builder which simplify the formatting of the graphql query.

```php
<?php

$query = $graphQlApiClientRegistry->get('my_client')->buildQuery($action, array $requestedFields): GraphQLQuery;
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

Cache
-----

Create new cache adapter provider(s) in your ```config/packages/cache_adapter.yaml``` ([official docs](http://www.php-cache.com/en/latest/symfony/adapter-bundle/))

```yaml
cache_adapter:
    providers:
        my_first_provider:
            factory: cache.factory.redis
            options:
                host: redis.example.com
                port: 6379
                database: 0
        my_second_provider:
            factory: cache.factory.redis
            options:
                host: redis.example.com
                port: 6379
                database: 1

```

Update your configuration in ```config/packages/idci_graphql_client.yaml```

```yaml
idci_graphql_client:
    cache_enabled: true
    clients:
        my_client_one:
            http_client: 'eight_points_guzzle.client.my_guzzle_client_one'
            cache: 'cache.provider.my_first_provider'
            cache_ttl: 3600
        my_client_two:
            http_client: 'eight_points_guzzle.client.my_guzzle_client_two'
            cache: 'cache.provider.my_second_provider'
            cache_ttl: 60
```

Now when your client execute a query the result will be inserted or retrieved from your cache provider

You can also activate/desactivate cache for a specific environment by adding a new yaml configuration file in ```config/packages/dev/``` or ```config/packages/test/```, for example:

```yaml
# config/packages/dev/idci_graphql_client.yaml
idci_graphql_client:
    cache_enabled: false
```

Command
-------

You can select which cache you want purged by using

```shell
$ php bin/console cache:graphql:clear
```
