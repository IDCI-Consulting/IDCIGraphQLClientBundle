<?php

namespace IDCI\Bundle\GraphQLClientBundle\Query;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientInterface;

class GraphQLQueryBuilder
{
    private string $type = GraphQLQuery::QUERY_TYPE;
    private string $action;
    private array $arguments = [];
    private array $requestedFields = [];
    private GraphQLApiClientInterface $client;

    public function __construct(GraphQLApiClientInterface $client)
    {
        $this->client = $client;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function addArgument(string $name, $value): self
    {
        if (isset($this->arguments[$name])) {
            throw new \Exception(sprintf('The argument "%s" already exist', $name));
        }

        $this->arguments[$name] = $value;

        return $this;
    }

    public function setArgument(string $name, $value): self
    {
        $this->arguments[$name] = $value;

        return $this;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function addRequestedField(string $name, $fields): self
    {
        if (isset($this->requestedFields[$name])) {
            throw new \Exception(sprintf('The requested field "%s" already exist', $name));
        }

        $this->requestedFields[$name] = $fields;

        return $this;
    }

    public function setRequestedField(string $name, $fields): self
    {
        $this->requestedFields[$name] = $fields;

        return $this;
    }

    public function setRequestedFields(array $requestedFields): self
    {
        $this->requestedFields = $requestedFields;

        return $this;
    }

    public function getQuery(): GraphQLQuery
    {
        $action = $this->action;

        if (!empty($this->arguments)) {
            $action = [$action => $this->arguments];
        }

        return new GraphQLQuery($this->type, $action, $this->requestedFields, $this->client);
    }
}
