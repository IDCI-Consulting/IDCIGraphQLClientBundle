<?php

namespace IDCI\Bundle\GraphQLClientBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class PurgeGraphQLCacheCommand extends ContainerAwareCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('graphql:cache:clear')
            ->setDescription('Clear graphql query cache')
            ->setHelp('Clear results of graphql queries in cache')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command.
Here is an example:

# Clear graphql queries cache
<info>php bin/console %command.name%</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clients = $this->getContainer()->getParameter('idci_graphql_client.clients');
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select the graphql client',
            array_keys($clients),
            0
        );

        $question->setErrorMessage('%s is an invalid choice.');
        $clientName = $helper->ask($input, $output, $question);

        if (!isset($clients[$clientName]['cache'])) {
            $output->writeln(sprintf('<error>No cache found for client "%s"</error>', $clientName));
            exit;
        }

        $this->getContainer()->get($clients[$clientName]['cache'])->clear();
        $this->getContainer()->get($clients[$clientName]['cache'])->commit();

        $output->writeln(sprintf('<info>Cache cleared for client "%s"</info>', $clientName));
    }
}
