<?php

namespace Cordoval\HackingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class HackCommand extends ContainerAwareCommand
{
    protected $workingDir;

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
        $appDir = $this->getContainer()->get('kernel')->getRootDir();
        $repoFullName = $input->getArgument('repoName');
        $shaOrTag = $input->getArgument('shaOrTag');
        $branchName = $input->getArgument('branchName');
        $issueNumber = $input->getArgument('issueNumber');
        $username = $this->getContainer()->getParameter('github.username');
        $randomName = uniqid().'.php';

        $fs = new Filesystem();
        $urlizedName = str_replace('/', '-', $repoFullName);
        list($rootRepo, $repoName) = explode('/', $repoFullName);
        $fs->mkdir("clones/$urlizedName-$branchName");
        $this->workingDir = $appDir."/../clones/$urlizedName-$branchName";

        $commands = array(
            array("git", "clone", "git@github.com:$repoFullName", "."),
            array("git", "remote", "update"),
            array("git", "checkout", "$shaOrTag"),
            array("git", "checkout", "-b", "$branchName"),
            array("composer", "install"),
            array("touch", "$randomName"),
            array("git", "add", "."),
            array("git", "commit", "-am", "\"starting work on #$issueNumber\""),
        );

        if ($rootRepo != $username) {
            $commands[] = array("git", "remote", "add", "$username", "git@github.com:$username/$repoName.git");
        }

        $commands[] = array("git", "push", "-u", "$username", "$branchName");

        foreach ($commands as $command) {
            $this->runItem($command);
        }
    }

    protected function runItem(array $command)
    {
        $builder = new ProcessBuilder($command);
        $builder
            ->setWorkingDirectory($this->workingDir)
            ->setTimeout(3600)
        ;
        $process = $builder->getProcess();
        $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer;
                } else {
                    echo 'OUT > ' . $buffer;
                }
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}