<?php

namespace Cordoval\HackingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HackingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hack')
            ->setDescription('Hack build')
            ->addArgument('repoName', InputArgument::REQUIRED, 'repo name e.g. symfony/symfony')
            ->addArgument('shaOrTag', InputArgument::REQUIRED, 'ref e.g. 459385495 or 2.3.32')
            ->addArgument('branchName', InputArgument::REQUIRED, 'branch name e.g. reproduce-bug-123')
            ->addArgument('issueNumber', InputArgument::REQUIRED, 'issue number e.g. 123')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repoName = $input->getArgument('repoName');
        $shaOrTag = $input->getArgument('shaOrTag');
        $branchName = $input->getArgument('branchName');
        $issueNumber = $input->getArgument('issueNumber');
        $username = $this->getContainer()->getParameter('github.username');

        $commands = array(
            "mkdir -p clones/$repoName-$branchName; cd clones/$repoName-$branchName",
            "git clone git@github.com:$repoName .; git remote update;",
            "git checkout $shaOrTag",
            "git checkout -b $branchName",
            "composer install",
            "phpunit",
            // create random file
            // git ca file
            "git add .; git commit -am \"starting work on #$issueNumber\"",
            "hub fork",
            "git push -u $username $branchName",
        );
    }
}