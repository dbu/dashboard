<?php

namespace Dbu\GhCollectorBundle\Github;

use Github\Client;

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

    public function __construct(
        Client $github,
        \Elastica\Type $repositoryType,
        \Elastica\Type $pullType,
        \Elastica\Type $issueType
    ) {
        $this->github = $github;
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
        /** @var $repoApi \Github\Api\Repo */
        $repoApi = $this->github->api('repo');
        /** @var $gitDataApi \Github\Api\GitData */
        $gitDataApi = $this->github->api('git_data');

        try {
            $repositories = $user->repositories($ghaccount);
        } catch (\Github\Exception\RuntimeException $e) {
            $this->log("<error>User '$ghaccount' not found</error>");

            return false;
        }
        try {
            $repositories = array_merge($repositories, $org->repositories($ghaccount));
        } catch (\Github\Exception\RuntimeException $e) {
            // we don't care, was probably just a normal user
        }

        foreach ($repositories as $repository) {
            $repository['owner_login'] = $repository['owner']['login'];
            unset($repository['owner']);

            $prDocs = $this->cleanItems($prApi->all($ghaccount, $repository['name']), $repository['id'], 'github_pull');
            $repository['open_pull_count'] = count($prDocs);

            if ($repository['has_issues']) {
                $issueDocs = $this->cleanItems($issueApi->all($ghaccount, $repository['name']), $repository['id'], 'github_issue', true);
            } else {
                // TODO: fetch from jira i.e. for phpcr-odm
                $issueDocs = array();
            }
            $repository['open_issues_only'] = count($issueDocs);

            if ($prDocs) {
                $this->pullType->addDocuments($prDocs);
            }
            if ($issueDocs) {
                $this->issueType->addDocuments($issueDocs);
            }

            // fetch details on repository
            $tags = $repoApi->tags($ghaccount, $repository['name']);

            $repository['last_tag'] = isset($tags[0]) ? $tags[0] : array();

            $this->repositoryType->addDocument(
                new \Elastica\Document($repository['id'], $repository, 'github_repository')
            );
        }

        return true;
    }

    /**
     * @param $items
     * @param $repositoryId
     * @param $docType
     * @param Boolean $skipPr
     *
     * @return \Elastica\Document[]
     */
    private function cleanItems($items, $repositoryId, $docType, $skipPr = false)
    {
        $docs = array();

        foreach ($items as $r) {
            if ($skipPr && !empty($r['pull_request']['html_url'])) {
                continue;
            }
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
            $doc->setParent($repositoryId);
            $docs[] = $doc;
        }

        return $docs;
    }

    private function log($message)
    {
    }
}
