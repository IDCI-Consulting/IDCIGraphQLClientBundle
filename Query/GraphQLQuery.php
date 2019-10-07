<?php

namespace IDCI\Bundle\GraphQLClientBundle\Query;

use GraphQL\Graph;
use GraphQL\Mutation;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientInterface;
use Symfony\Component\HttpFoundation\File\File;

class GraphQLQuery
{
    const MUTATION_TYPE = 'mutation';

    const QUERY_TYPE = 'query';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|array
     */
    private $action;

    /**
     * @var array
     */
    private $actionParameters;

    /**
     * @var array
     */
    private $requestedFields;

    /**
     * @var string
     */
    private $query;

    /**
     * @var GraphQLApiClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $files;

    public function __construct(string $type, $action, array $requestedFields, GraphQLApiClientInterface $client)
    {
        if (!is_array($action) && !is_string($action)) {
            throw new \InvalidArgumentException('action parameter must be a string or an array');
        }

        if (self::MUTATION_TYPE !== $type && self::QUERY_TYPE !== $type) {
            throw new \InvalidArgumentException(
                sprintf('query type must be a "%s" or "%s"', self::MUTATION_TYPE, self::QUERY_TYPE)
            );
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

        $this->query = $graphQlQuery;
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

    public function getAction(): string
    {
        return $this->action;
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

    public function getResults($cache = true)
    {
        if (self::MUTATION_TYPE === $this->type) {
            $cache = false;
        }

        return $this->client->query($this, $cache);
    }

    public function getHash()
    {
        return hash('sha1', $this->query);
    }

    private function buildGraph($field, $key, &$graphQlQuery)
    {
        if (!is_array($field)) {
            return $graphQlQuery->use($field);
        }

        if (array_key_exists('_parameters', $field)) {
            array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key($field['_parameters']));
        } elseif ('_fragments' === $key) {
            foreach ($field as $fragment => $subfield) {
                array_walk($subfield, [$this, 'buildGraph'], $graphQlQuery->on($fragment));
            }
        } elseif ('_parameters' !== $key) {
            array_walk($field, [$this, 'buildGraph'], $graphQlQuery->$key);
        }
    }
}
