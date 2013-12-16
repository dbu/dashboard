<?php

namespace Dbu\GhCollectorBundle\Command;

use Dbu\DashboardBundle\Model\BufferedOutput;
use Dbu\DashboardBundle\Model\Question;
use Dbu\DashboardBundle\Model\Questionary;
use Dbu\DashboardBundle\Model\SymfonyQuestionary;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class PullRequestCommand extends Command
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
        $pullRequestNumber = $this->postPullRequest();
        $client = $this->api;
        $pullRequest = $client->get('pull-request', $pullRequestNumber);
        $client->post('pull-request', $pullRequest, $tableString);
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
}