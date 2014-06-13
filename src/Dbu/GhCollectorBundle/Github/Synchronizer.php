<?php

namespace Dbu\GhCollectorBundle\Github;

use Github\Api\Repo;
use Github\Client;
use Github\ResultPager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

class Synchronizer
{
    /** @var Client */
    private $github;
    /** @var \Elastica\Type */
    private $repositoryType;
    /** @var \Elastica\Type */
    private $pullType;
    /** @var \Elastica\Type */
    private $issueType;
    /**
     * @var \Github\ResultPager
     */
    private $paginator;

    /**
     * @var OutputInterface|null
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Client $github,
        \Elastica\Type $repositoryType,
        \Elastica\Type $pullType,
        \Elastica\Type $issueType,
        LoggerInterface $logger
    ) {
        $this->github = $github;
        $this->paginator = new ResultPager($this->github);
        $this->repositoryType = $repositoryType;
        $this->pullType = $pullType;
        $this->issueType = $issueType;
        $this->logger = $logger;
    }

    /**
     * @param  string  $ghaccount github account name (user or organization)
     * @return Boolean
     */
    public function synchronize($ghaccount)
    {
        $this->log(LogLevel::DEBUG, "Synchronizing $ghaccount");
        /** @var $user \Github\Api\User */
        $user = $this->github->api('user');
        /** @var $prApi \Github\Api\PullRequest */
        $prApi = $this->github->api('pull_request');
        /** @var $issueApi \Github\Api\Issue */
        $issueApi = $this->github->api('issue');
        /** @var $repo \Github\Api\Repo */
        $repo = $this->github->api('repo');

        try {
            $repositories = $this->paginator->fetchAll($user, 'repositories', array($ghaccount));
        } catch (\Github\Exception\RuntimeException $e) {
            $this->log(LogLevel::CRITICAL, "<error>User '$ghaccount' not found</error>");

            return false;
        }

        foreach ($repositories as $repository) {
            $repository['owner_login'] = $repository['owner']['login'];
            unset($repository['owner']);
            $repository['composer_name'] = $this->getComposerName($repo, $repository);

            try {
                $items = $this->paginator->fetchAll($prApi, 'all', array($ghaccount, $repository['name']));
            } catch(\Github\Exception\RuntimeException $e) {
                $this->log(LogLevel::ERROR, "<error>Repository $ghaccount/{$repository['name']} failed:</error>\n" . $e->getMessage());

                continue;
            }
            $prDocs = $this->cleanItems(
                $items,
                $repository,
                'github_pull'
            );
            $repository['open_pull_count'] = count($prDocs);

            if ($repository['has_issues']) {
                $issueDocs = $this->cleanItems(
                    $this->paginator->fetchAll($issueApi, 'all', array($ghaccount, $repository['name'])),
                    $repository,
                    'github_issue',
                    true
                );
            } else {
                $this->log(LogLevel::INFO, "Repository $ghaccount/{$repository['name']} has github issues deactivated.");
                $issueDocs = array();
            }
            $repository['open_issues_only'] = count($issueDocs);

            if ($prDocs) {
                $this->pullType->addDocuments($prDocs);
            }
            if ($issueDocs) {
                $this->issueType->addDocuments($issueDocs);
            }

            $this->repositoryType->addDocument(
                new \Elastica\Document($repository['id'], $repository, 'github_repository')
            );
        }

        return true;
    }

    private function getComposerName(Repo $repo, array $repository)
    {
        try {
            $composer = $repo->contents()->show($repository['owner_login'], $repository['name'], 'composer.json');
            if ('base64' === $composer['encoding']) {
                $composer = json_decode(base64_decode($composer['content']));

                return isset($composer->name) ? $composer->name : false;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param $items
     * @param $repositoryId
     * @param $docType
     * @param Boolean $skipPr
     *
     * @return \Elastica\Document[]
     */
    private function cleanItems($items, $repository, $docType, $skipPr = false)
    {
        $docs = array();

        foreach ($items as $r) {
            if ($skipPr && !empty($r['pull_request']['html_url'])) {
                continue;
            }
            // de-normalize repository information into issue
            $r['name'] = $repository['name'];
            $r['full_name'] = $repository['full_name'];
            $r['owner_login'] = $repository['owner_login'];

            $r['user_login'] = $r['user']['login'];
            unset($r['user']);
            $r['assignee_login'] = isset($r['assignee']['login']) ? $r['assignee']['login'] : false;
            unset($r['assignee']);
            unset($r['head']);
            unset($r['base']);
            if ($r['milestone']) {
                $r['milestone_title'] = $r['milestone']['title'];
            }
            unset($r['milestone']);
            $doc = new \Elastica\Document($r['id'], $r, $docType);
            $doc->setParent($repository['id']);
            $docs[] = $doc;
        }

        return $docs;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    private function log($level, $message)
    {
        $this->logger->log($level, $message);

        if ($this->output
            && ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL
                || in_array($level, array(LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR))
        )) {
            $this->output->writeln($message);
        }
    }
}
