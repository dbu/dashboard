<?php

namespace Cordoval\HackingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReproduceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('reproduce')
            ->setDescription('Reproduce PR')
            ->addArgument('repoName', InputArgument::REQUIRED, 'repo name e.g. symfony/symfony')
            ->addArgument('shaOrTag', InputArgument::REQUIRED, 'ref e.g. 459385495 or 2.3.32')
            ->addArgument('branchName', InputArgument::REQUIRED, 'branch name e.g. reproduce-bug-123')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repoName = $input->getArgument('repoName');
        $shaOrTag = $input->getArgument('shaOrTag');
        $branchName = $input->getArgument('branchName');


    }
}