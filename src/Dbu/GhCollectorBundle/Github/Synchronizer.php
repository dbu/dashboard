<?php

namespace Dbu\GhCollectorBundle\Github;

use Github\Api\Repo;
use Github\Client;
use Github\ResultPager;

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
    private $paginator;

    public function __construct(
        Client $github,
        \Elastica\Type $repositoryType,
        \Elastica\Type $pullType,
        \Elastica\Type $issueType
    ) {
        $this->github = $github;
        $this->paginator = new ResultPager($this->github);
        $this->repositoryType = $repositoryType;
        $this->pullType = $pullType;
        $this->issueType = $issueType;
    }

    /**
     * @param  string  $ghaccount github account name (user or organization)
     * @return Boolean
     */
    public function synchronize($ghaccount)
    {
        /** @var $user \Github\Api\User */
        $user = $this->github->api('user');
        /** @var $prApi \Github\Api\PullRequest */
        $prApi = $this->github->api('pull_request');
        /** @var $issueApi \Github\Api\Issue */
        $issueApi = $this->github->api('issue');
        /** @var $org \Github\Api\Organization */
        $org = $this->github->api('organization');
        /** @var $repo \Github\Api\Repo */
        $repo = $this->github->api('repo');

        try {
            $repositories = $this->paginator->fetchAll($user, 'repositories', array($ghaccount));
        } catch (\Github\Exception\RuntimeException $e) {
            $this->log("<error>User '$ghaccount' not found</error>");

            return false;
        }
        try {
            $repositories = array_merge($repositories, $this->paginator->fetchAll($org, 'repositories', array($ghaccount)));
        } catch (\Github\Exception\RuntimeException $e) {
            // we don't care, was probably just a normal user
        }

        foreach ($repositories as $repository) {
            $repository['owner_login'] = $repository['owner']['login'];
            unset($repository['owner']);
            $repository['composer_name'] = $this->getComposerName($repo, $repository);

            $prDocs = $this->cleanItems(
                $this->paginator->fetchAll($prApi, 'all', array($ghaccount, $repository['name'])),
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
                $this->log("Repository {$repository['name']} has github issues deactivated.");
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
                return $composer->name;
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

    private function log($message)
    {
    }
}
