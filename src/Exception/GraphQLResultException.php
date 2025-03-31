<?php

namespace IDCI\Bundle\GraphQLClientBundle\Exception;

final class GraphQLResultException extends \Exception
{
    private string $graphQlQuery;
    private array $result;

    public function __construct(string $graphQlQuery, array $result)
    {
        $this->graphQlQuery = $graphQlQuery;
        $this->result = $result;

        parent::__construct($this->buildMessage());
    }

    public function getGraphQlQuery(): string
    {
        return $this->graphQlQuery;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function getErrors(): array
    {
        return $this->result['errors'] ?? [];
    }

    public function getData(): array
    {
        return $this->result['data'] ?? [];
    }

    private function buildMessage(): string
    {
        if (!empty($this->getErrors())) {
            return sprintf(
                'Error occured during the execution of GraphQL query "%s". Errors: %s',
                $this->getGraphQlQuery(),
                json_encode($this->getErrors())
            );
        }

        if (empty($this->getData())) {
            return sprintf(
                'Error occured during the execution of GraphQL query "%s". Empty data.',
                $this->getGraphQlQuery()
            );
        }

        return sprintf(
            'Error occured during the execution of GraphQL query "%s". Result: %s',
            $this->getGraphQlQuery(),
            json_encode($this->getResult())
        );
    }
}

// $this->logger->warning('', [
//     'query' => $graphQlQuery->getGraphQlQuery(),
//     'result' => $result,
// ]);
