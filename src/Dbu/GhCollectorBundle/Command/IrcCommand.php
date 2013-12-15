<?php

namespace Dbu\GhCollectorBundle\Command;

use Phoebe\ConnectionManager;
use Phoebe\Connection;
use Phoebe\Event\Event;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\PluginInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dbu\GhCollectorBundle\Github\Plugin\HackingdayPlugin;

class IrcCommand extends Command
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
        $nickname = $input->getOption('nickname');
        $channels = $input->getArgument('channels');

        $connectionManager = $this->getConnectionManager();
        $connectionManager->addConnection($this->getFreenodeConnection($nickname, $channels));
        $connectionManager->run();
    }

    /**
     * @param string $nickname
     * @param array  $channels
     *
     * @return Connection
     */
    private function getFreenodeConnection($nickname, array $channels)
    {
        $connection = new Connection();
        $connection->setServerHostname('irc.freenode.net');
        $connection->setServerPort(6667);
        $connection->setNickname($nickname);
        $connection->setUsername($nickname);
        $connection->setRealname($nickname);

        $dispatcher = $connection->getEventDispatcher();

        foreach ($this->getPlugins() as $plugin) {
            $dispatcher->addSubscriber($plugin);
        }

        $dispatcher->addListener('irc.received.001', function (Event $event) use ($channels) {
            foreach ($channels as $channel) {
                $event->getWriteStream()->ircJoin('#'.$channel);
            }
        });

        return $connection;
    }

    /**
     * @return ConnectionManager
     */
    private function getConnectionManager()
    {
        return new ConnectionManager();
    }

    /**
     * @return PluginInterface[]
     */
    private function getPlugins()
    {
        $hackingdayPlugin = new HackingdayPlugin();
        new \SplObjectStorage();
        return array(
            new PingPongPlugin(),
            $hackingdayPlugin,
        );
    }
}