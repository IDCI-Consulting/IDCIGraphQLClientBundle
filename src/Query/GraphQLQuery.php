<?php

namespace IDCI\Bundle\GraphQLClientBundle\Query;

use GraphQL\Graph;
use GraphQL\Mutation;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientInterface;
use Symfony\Component\HttpFoundation\File\File;

class GraphQLQuery
{
    public const string MUTATION_TYPE = 'mutation';
    public const string QUERY_TYPE = 'query';

    private string $type;
    private string|array $action;
    private ?string $endpoint = null;
    private array $actionParameters;
    private array $requestedFields;
    private string $query;
    private GraphQLApiClientInterface $client;
    private array $files = [];
    private array $headers = [];
    private ?string $locale = null;

    public function __construct(string $type, $action, array $requestedFields, GraphQLApiClientInterface $client)
    {
        if (!is_array($action) && !is_string($action)) {
            throw new \InvalidArgumentException('action parameter must be a string or an array');
        }

        if (self::MUTATION_TYPE !== $type && self::QUERY_TYPE !== $type) {
            throw new \InvalidArgumentException(sprintf('query type must be a "%s" or "%s"', self::MUTATION_TYPE, self::QUERY_TYPE));
        }

        $this->type = $type;
        $this->action = $action;
        $this->requestedFields = $requestedFields;
        $this->client = $client;

        if (is_array($action)) {
            $key = array_keys($action)[0];

            if (0 === $key) {
                throw new \InvalidArgumentException('Action parameters must be associative array');
            }

            $this->action = $key;
            $this->actionParameters = $action[$key];

            if (self::QUERY_TYPE === $this->type) {
                $graphQlQuery = new Graph($key, $action[$key]);
            } else {
                $graphQlQuery = new Mutation($key, $action[$key]);
            }
        } else {
            if (self::QUERY_TYPE === $this->type) {
                $graphQlQuery = new Graph($action);
            } else {
                throw new \InvalidArgumentException('You must pass parameters when performing mutations!');
            }
        }

        array_walk($requestedFields, [$this, 'buildGraph'], $graphQlQuery);

        $this->query = $this->decodeGraphQlQuery($graphQlQuery);
    }

    public function addFile(File $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function hasFiles(): bool
    {
        return !empty($this->files);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function addHeader(string $key, $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function removeHeader(string $key): self
    {
        unset($this->headers[$key]);

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function hasEndpoint(): bool
    {
        return null !== $this->endpoint;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getActionParameters(): array
    {
        return $this->actionParameters;
    }

    public function getRequestedFields(): array
    {
        return $this->requestedFields;
    }

    public function getGraphQLQuery(): string
    {
        return $this->query;
    }

    public function setGraphQLQuery(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getResults($cache = true)
    {
        if (self::MUTATION_TYPE === $this->type) {
            $cache = false;
        }

        return $this->client->query($this, $cache);
    }

    public function getHash()
    {
        return hash(
            'sha1',
            sprintf(
                '%s%s%s%s',
                $this->getEndpoint(),
                $this->getLocale(),
                json_encode($this->getHeaders()),
                $this->getGraphQLQuery()
            )
        );
    }

    private function buildGraph($field, $key, $graphQlQuery)
    {
        if (!is_array($field)) {
            if (in_array($key, ['_parameters', '_alias'], true)) {
                return;
            }

            return $graphQlQuery->use($field);
        }

        if (array_key_exists('_parameters', $field)) {
            if (array_key_exists('_alias', $field)) {
                $alias = $field['_alias'];

                return array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$alias($field['_parameters'])->alias($key));
            }

            return array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key($field['_parameters']));
        }

        if ('_fragments' === $key) {
            foreach ($field as $fragment => $subfield) {
                array_walk($subfield, [$this, 'buildGraph'], $graphQlQuery->on($fragment));
            }

            return;
        }

        if (!in_array($key, ['_parameters', '_alias'])) {
            if (array_key_exists('_alias', $field)) {
                $alias = $field['_alias'];

                return array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$alias->alias($key));
            }

            return array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key);
        }
    }

    private function decodeGraphQlQuery(string $graphQlQuery)
    {
        $graphQlQuery = preg_replace('/{\n\s*}/', '', $graphQlQuery);

        return preg_replace_callback('/(\\\\|\\\\\\\\)u([a-f0-9]{4})/', function ($param) {
            return json_decode(sprintf('["%s"]', str_replace('\\\\', '\\', $param[0])))[0];
        }, $graphQlQuery);
    }
}
