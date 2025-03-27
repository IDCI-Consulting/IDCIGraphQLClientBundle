<?php

namespace IDCI\Bundle\GraphQLClientBundle\Command;

use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClient;
use IDCI\Bundle\GraphQLClientBundle\Client\GraphQLApiClientRegistryInterface;
use IDCI\Bundle\GraphQLClientBundle\Query\GraphQLQueryFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class QueryCommand extends Command
{
    /**
     * @var GraphQLApiClientRegistryInterface
     */
    private $graphQLApiClientRegistry;

    public function __construct(GraphQLApiClientRegistryInterface $graphQLApiClientRegistry)
    {
        $this->graphQLApiClientRegistry = $graphQLApiClientRegistry;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('graphql:query')
            ->setDescription('Execute graphql query')
            ->addArgument('query', InputArgument::REQUIRED, 'The graphql query which will be executed')
            ->addOption('cache', null, InputOption::VALUE_NONE, 'If the query should be stored in cache')
            ->setHelp('Execute graphql query')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command.
Here is an example:

# Execute graphql query
<info>php bin/console %command.name%</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clients = $this->graphQLApiClientRegistry->getAll();
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select the graphql client',
            array_keys($clients),
            0
        );

        $question->setErrorMessage('%s is an invalid choice.');
        $clientName = $helper->ask($input, $output, $question);

        $client = $clients[$clientName];

        $output->writeln(sprintf('<info>Executing query for client "%s"</info>', $clientName));

        $query = GraphQLQueryFactory::fromString($input->getArgument('query'));

        $start = microtime(true);

        try {
            $result = $client->query($query, $input->getOption('cache'));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error during the execution of the request: "%s"</error>', $e->getMessage()));

            return 1;
        }

        $output->writeln(sprintf('<info>Query has been successfully executed, time spent: "%s"</info>', microtime(true) - $start));
        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));

        return 0;
    }
}
