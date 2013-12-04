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
 * Command to launch the github synchronizer
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class SynchronizeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('dbu:sync')
// TODO: implement updating. until we have that, specifying organizations on the CLI makes no sense
//            ->addArgument('organization', InputArgument::IS_ARRAY, 'list of user resp organisation names to synchronize instead of configured ones')
//            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update instead of rebuild the index')
            ->setDescription('Synchronize configured github repositories into elasticsearch')
            ->setHelp(<<<EOF
The command <info>%command.name%</info> fetches information about open issues and pull requests from github:

  <info>php %command.full_name%/info>

EOF
/* Note: Listing organizations that are not configured will lead to them being in the search index
   but never shown on the dashboard.
   Note: organization names are both real organizations and single users. We always sync all repositories of an organization.
*/
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        if (!$input->getOption('update')) {
            /** @var $resetter Resetter */
            $resetter = $this->getContainer()->get('fos_elastica.resetter');
            $resetter->resetAllIndexes();
//        }

        /** @var $synchronizer Synchronizer */
        $synchronizer = $this->getContainer()->get('dbu_gh_collector.github.synchronizer');

        $organizations =
            $input->hasArgument('organziation')
            ? $input->getArgument('organization')
            : $this->getOrganizations()
        ;

        foreach ($organizations as $organization) {
            $synchronizer->synchronize($organization);
        }
    }

    /**
     * Get configured organizations.
     *
     * @return array
     */
    private function getOrganizations()
    {
        return array_keys($this->getContainer()->getParameter('repositories'));
    }
}
