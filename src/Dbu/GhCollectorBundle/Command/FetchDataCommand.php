<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dbu\GhCollectorBundle\Command;

use FOS\ElasticaBundle\Resetter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Dbu\GhCollectorBundle\Github\Synchronizer;


/**
 * @license GPL
 */
class FetchDataCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('github:fetch')
            ->addArgument('repository', InputArgument::IS_ARRAY, 'user or user/repository')
            ->setDescription('A command to query data from github')
            ->setHelp(<<<EOF
The command <info>%command.name%</info> fetches information about open pull requests from github:

  <info>php %command.full_name% phpcr/phpcr-utils doctrine/phpcr-odm</info>

Note that while we say "user" in the filter, this can also be an organization name.
EOF
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: dev...
        /** @var $resetter Resetter */
        $resetter = $this->getContainer()->get('fos_elastica.resetter');
        $resetter->resetAllIndexes();

        /** @var $synchronizer Synchronizer */
        $synchronizer = $this->getContainer()->get('dbu_gh_collector.github.synchronizer');

        foreach ($this->getUsers() as $user) {
            $synchronizer->synchronize($user);
        }
    }

    private function getUsers()
    {
        return array_keys($this->getContainer()->getParameter('repositories'));
    }
}
