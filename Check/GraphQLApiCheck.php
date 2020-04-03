<?php

namespace IDCI\Bundle\GraphQLClientBundle\Check;

use GuzzleHttp\ClientInterface;
use Laminas\Diagnostics\Check\CheckInterface;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\Success;
use Laminas\Diagnostics\Result\Warning;
use Symfony\Component\HttpFoundation\Response;

if (interface_exists(CheckInterface::class)) {
    class GraphQLApiCheck implements CheckInterface
    {
        /**
         * @var ClientInterface
         */
        private $client;

        /**
         * @var string
         */
        private $name;

        public function __construct(?ClientInterface $client = null, ?string $name = null)
        {
            $this->client = $client;
            $this->name = $name;
        }

        public function check()
        {
            if (null === $this->client) {
                return new Warning('The checker is misconfigured: guzzle client is null.');
            }

            try {
                $response = $this->client->request('POST', '', [
                    'headers' => [
                        'Accept-Encoding' => 'gzip',
                    ],
                    'form_params' => [
                        'query' => '{}',
                    ],
                ]);

                if (Response::HTTP_OK === $response->getStatusCode()) {
                    return new Success(
                        sprintf('The %s GraphQL API is accessible.', $this->name)
                    );
                }

                return new Warning(sprintf('Unexpected %s status code.', $response->getStatusCode()));
            } catch (\Exception $e) {
                return new Failure($e->getMessage());
            }
        }

        public function getLabel()
        {
            return sprintf('Check if %s GraphQL API is currently accessible', $this->name);
        }
    }
}
