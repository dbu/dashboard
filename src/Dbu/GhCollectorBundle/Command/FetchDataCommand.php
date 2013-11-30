<?php

namespace Dbu\GhCollectorBundle\Command;

use Github\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class FetchDataCommand extends ContainerAwareCommand
{
    private $username;
    private $password;
    private $repositories;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('github:fetch')
            ->addArgument('repository', InputArgument::IS_ARRAY, 'user or user/repository')
            ->setDescription('A command to query data from github')
            ->setHelp(<<<EOF
The command <info>%command.name%</info> fetches information about open pull requests from github:

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
        $container = $this->getContainer();
        $this->username = $container->getParameter('github.username');
        $this->password = $container->getParameter('github.password');
        $this->repositories = $container->getParameter('repositories');

        $this->configureStyles($output);
        $client = $this->getClient();

        $users = $this->getUserRepositories($input);

        /** @var $user \Github\Api\User */
        $user = $client->api('user');
        /** @var $pr \Github\Api\PullRequest */
        $prApi = $client->api('pull_request');
        /** @var $repo \Github\Api\Repo */
        $repo = $client->api('repo');

        foreach($users as $userName => $data) {
            if (is_array($data) && isset($data['include'])) {
                $repositories = array();
                foreach ($data['include'] as $repoName) {
                    try {
                        $repositories[] = $repo->show($userName, $repoName);
                    } catch(\Github\Exception\RuntimeException $e) {
                        $output->writeln("<error>No user '$userName' or no repository '$repoName'</error>");
                    }
                }
            } else {
                try {
                    $repositories = $user->repositories($userName);
                } catch (\Github\Exception\RuntimeException $e) {
                    $output->writeln("<error>User '$userName' not found</error>");
                    continue;
                }
                if (empty($repositories)) {
                    $output->writeln("<error>User '$userName' has no public repositories</error>");
                }
                if (isset($data['exclude'])) {
                    foreach ($repositories as $repoName => $repo) {
                        if (in_array($repoName, $data['exclude'])) {
                            unset($repositories[$repoName]);
                        }
                    }
                }
            }

            foreach($repositories as $repository) {

                if ($repository['fork']) {
                    continue;
                }
                $pullRequests = $prApi->all($userName, $repository['name']);
                if (! count($pullRequests)) {
                    continue;
                }
                $output->write(sprintf('<title>%s</title>', $repository['name']));
                if ($repository['fork']) {
                    $output->write(' (forked)');
                }
                $output->writeln(sprintf(' - %s open PR', count($pullRequests)));
                $output->writeln('');

                foreach($pullRequests as $r) {
                    $output->writeln(sprintf('  <subtitle>#%s %s</subtitle> (%s)', $r['number'], $r['title'], $r['user']['login']));
                    $created = strtotime($r['created_at']);
                    $updated = strtotime($r['updated_at']);
                    if (time() - $created > 60*60*24 * 60) {
                        $status = 'error';
                    } elseif (time() - $created > 60*60*24 * 30) {
                        $status = 'warning';
                    } else {
                        $status = 'info';
                    }
                    $output->writeln(sprintf('  <%s>Created: %s  Updated: %s</%s>', $status, date('d.m.Y', $created), date('d.m.Y', $updated), $status));
                    $output->writeln('  ' . $r['html_url']);
                    $output->writeln('');
                }
                $output->writeln('');
            }
        }
    }

    private function getUserRepositories(InputInterface $input)
    {
        $users = array();
        if (! count($input->getArgument('repository'))) {
            return $this->repositories;
        }

        foreach ($input->getArgument('repository') as $argument) {
            if (strpos($argument, '/')) {
                list($user, $repo) = explode('/', $argument);
                if (!isset($users[$user]['include'])) {
                    $users[$user]['include'] = array();
                }
                $users[$user]['include'][] = $repo;
            } else {
                $users[$argument] = true;
            }
        }

        return $users;
    }

    private function getClient()
    {
        $client = new Client;
        $client->authenticate(
            $this->username,
            $this->password,
            Client::AUTH_HTTP_PASSWORD
        );
        return $client;
    }

    private function configureStyles(OutputInterface $output)
    {
        $warning = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('warning', $warning);
        $title = new OutputFormatterStyle('blue', null, array('bold'));
        $output->getFormatter()->setStyle('title', $title);
        $subtitle = new OutputFormatterStyle('green', null, array('bold'));
        $output->getFormatter()->setStyle('subtitle', $subtitle);
        $output->getFormatter()->getStyle('error')->setOption('bold');
    }
}