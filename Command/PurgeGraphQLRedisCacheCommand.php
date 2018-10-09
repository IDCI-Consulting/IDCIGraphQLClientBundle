<?php

namespace IDCI\Bundle\GraphQLClientBundle\Command;

use App\Manager\GraphQLCacheManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeGraphQLRedisCacheCommand extends Command
{
    /**
     * @var GraphQLCacheManager
     */
    private $cache;

    public function __construct(GraphQLCacheManager $cache)
    {
        $this->cache = $cache;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('cache:graphql:clear')
            ->setDescription('Clear graphql query cache')
            ->setHelp('Clear result of graphql query in redis cache')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command.
Here is an example:

# Clear graphql query cache
<info>php bin/console %command.name%</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cache->purge();
    }
}