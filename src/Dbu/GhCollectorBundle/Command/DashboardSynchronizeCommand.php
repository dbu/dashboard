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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use FOS\ElasticaBundle\Resetter;

use Dbu\GhCollectorBundle\Github\Synchronizer;


/**
 * @license GPL
 */
class DashboardSynchronizeCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('dashboard:synchronize')
            ->addArgument('repository', InputArgument::IS_ARRAY, 'user (or organisation) or user/repository')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update instead of rebuild the index')
            ->setDescription('Synchronize github repositories into elasticsearch')
            ->setHelp(<<<EOF
The command <info>%command.name%</info> fetches information about open issues and pull requests from github:

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
        if (!$input->getOption('update')) {
            /** @var $resetter Resetter */
            $resetter = $this->getContainer()->get('fos_elastica.resetter');
            $resetter->resetAllIndexes();
        } else {
            throw new \Exception('Sorry, updating the index is not implemented yet - see https://github.com/dbu/dashboard/issues/6');
        }

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
