<?php

namespace Dbu\GhCollectorBundle\Command;

class MergeCommand extends Command
{
    protected function configure()
    {
        $this->setName('dbu:irc')
            ->setDescription('Starts the bot on irc')
            ->addOption('nickname', null, InputOption::VALUE_REQUIRED, 'Who do you want to be?', 'HackingDayBot')
            ->addArgument('channels', InputArgument::IS_ARRAY, 'List of channels to join', array('symfony-hackingday'))
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
        // gh merge 123 --switch=2.2
        // gh merge 123 --squash // merge into the latest tagged branch
        // changelog tool generation from ophiney??
        // gh move symfony/Console#1234
        // gh label --new --issues
        // git log --grep="bug *#" --merges --format="%s" v2.2.10...v2.2.11"
        // add labels to PRs

        $nickname = $input->getOption('nickname');
        $channels = $input->getArgument('channels');

        $connectionManager = $this->getConnectionManager();
        $connectionManager->addConnection($this->getFreenodeConnection($nickname, $channels));
        $connectionManager->run();
    }
}