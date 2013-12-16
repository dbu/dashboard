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
        $username = $this->getContainer()->getParameter('github.username');
        $repoName = $this->getRepoName();

        // push branch
        // assume we have all committed
        $commands = array(
            sprintf('git remote add %s git@github.com:%s/%s.git', $username, $repoName),
            'git remote update',
            /* here fork via github for later */
            sprintf('git push -u %s %s', $username, $this->extractBranchName()),
        );

        foreach ($commands as $command) {
            $this->runItem($explodedCommand = explode(' ', $command));
        }

        /** @var \Github\Client $client */
//        $client = $this->getContainer()->get('dbu_gh_collector.github.client');
//        $client->api('');
        // create pr with message
    }

    protected function runItem(array $command)
     {
         $builder = new ProcessBuilder($command);
         $builder
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

    /**
     * @return string
     */
    protected function extractBranchName()
    {
        $process = new Process('git branch | grep "*" | cut -d " " -f 2');
        $process->run();

        return $process->getOutput();
    }

    private function getRepoName()
    {
        $process = new Process('git remote show -n origin | grep Fetch | cut -d "/" -f 5 | cut -d "." -f 1');
        $process->run();

        return $process->getOutput();
    }

}