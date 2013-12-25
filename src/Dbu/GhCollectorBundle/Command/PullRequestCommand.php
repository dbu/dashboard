<?php

namespace Dbu\GhCollectorBundle\Command;

use Dbu\DashboardBundle\Model\BufferedOutput;
use Dbu\DashboardBundle\Model\Question;
use Dbu\DashboardBundle\Model\Questionary;
use Dbu\DashboardBundle\Model\SymfonyQuestionary;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class PullRequestCommand extends ContainerAwareCommand
{
    protected $workDir;

    protected function configure()
    {
        $this->setName('dbu:pr')
            ->setDescription('Pull request command')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->workDir = $this->getContainer()->getParameter('kernel.root_dir').'/../vendor/symfony/symfony';
        $tableString = $this->getGithubTableString($output);
        $prNumber = $this->postPullRequest($output, $tableString);
    }

    /**
     * @param OutputInterface $output
     * @return string
     */
    protected function getGithubTableString(OutputInterface $output)
    {
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        /** @var \Dbu\DashboardBundle\Model\Question[] $questions */
        $questionary = new SymfonyQuestionary();

        $answers = array();
        /** @var Question $question */
        foreach ($questionary->getQuestions() as $question) {
            $statement = $question->getStatement() . ' ';
            if ($question->getDefault()) {
                $statement .= '[' . $question->getDefault() . '] ';
            }

            // change this when on 2.5 to the new Question model
            $answers[] = array(
                $question->getStatement(),
                $dialog->askAndValidate(
                    $output,
                    $statement,
                    $question->getValidator(),
                    $question->getAttempt(),
                    $question->getDefault(),
                    $question->getAutocomplete()
                )
            );
        }

        $table = $this->getMarkdownTableHelper($questionary);
        $table->addRows($answers);

        $tableOutput = new BufferedOutput();
        $table->render($tableOutput);

        return $tableOutput->fetch();
    }

    /**
     * @param Questionary $questionary
     * @return TableHelper
     */
    protected function getMarkdownTableHelper(Questionary $questionary)
    {
        $table = $this->getHelper('table');

        /** @var TableHelper $table */
        $table
            ->setLayout(TableHelper::LAYOUT_DEFAULT)
            ->setVerticalBorderChar('|')
            ->setHorizontalBorderChar(' ')
            ->setCrossingChar(' ')
        ;

        // adds headers from questionary
        $table->addRow($questionary->getHeaders());
        // add rows --- | --- | ...
        $table->addRow(array_fill(0, count($questionary->getHeaders()), '---'));

        return $table;
    }

    protected function postPullRequest($output, $description)
    {
        $username = $this->getContainer()->getParameter('github.nickname');
        $repoName = $this->getRepoName();
        $branchName = $this->extractBranchName();
        $vendorName = 'cordoval';//;
        $baseBranch = 'cordoval/master';// $vendorName.'/'.$ref;
        $title = 'sample';

        $commands = array(
            array(
                'line' => sprintf('git remote add %s git@github.com:%s/%s.git', $username, $username, $repoName),
                'allow_failures' => true
            ),
            array(
                'line' => 'git remote update',
                'allow_failures' => false
            ),
            array(
                'line' => sprintf('git push -u %s %s', $username, $branchName),
                'allow_failures' => false
            )
        );

        foreach ($commands as $command) {
            $this->runItem($explodedCommand = explode(' ', $command['line']), $command['allow_failures']);
        }

        /** @var \Github\Client $client */
        $client = $this->getContainer()->get('dbu_gh_collector.github.client');
        $pullRequest = $client
            ->api('pull_request')
            ->create($vendorName, $repoName, array(
                'base'  => $baseBranch,
                'head'  => $username.'/'.$branchName,
                'title' => $title,
                'body'  => $description
            )
        );

        ladybug_dump($pullRequest);

        return $pullRequest;
    }

    protected function runItem(array $command, $allowFailures = false)
    {
         $builder = new ProcessBuilder($command);
         $builder
            ->setWorkingDirectory($this->workDir)
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

         if (!$process->isSuccessful() && !$allowFailures) {
            throw new \RuntimeException($process->getErrorOutput());
         }
    }

    protected function extractBranchName()
    {
        $process = new Process('git branch | grep "*" | cut -d " " -f 2', $this->workDir);
        $process->run();

        return trim($process->getOutput());
    }

    protected function getRepoName()
    {
        $process = new Process('git remote show -n origin | grep Fetch | cut -d "/" -f 5 | cut -d "." -f 1', $this->workDir);
        $process->run();

        return trim($process->getOutput());
    }
}