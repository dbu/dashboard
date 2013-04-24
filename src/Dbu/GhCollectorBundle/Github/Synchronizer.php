<?php

namespace Dbu\GhCollectorBundle\Github;

use Github\Client;

class Synchronizer
{
    /** @var Client */
    private $github;
    /** @var \Elastica_Type */
    private $repositoryType;
    /** @var \Elastica_type */
    private $pullType;

    public function __construct(
        Client $github,
        \Elastica_Type $repositoryType,
        \Elastica_type $pullType
    ) {
        $this->github = $github;
        $this->repositoryType = $repositoryType;
        $this->pullType = $pullType;
    }

    /**
     * @param string $ghaccount github account name (user or organization)
     */
    public function synchronize($ghaccount)
    {
        /** @var $user \Github\Api\User */
        $user = $this->github->api('user');
        /** @var $pr \Github\Api\PullRequest */
        $prApi = $this->github->api('pull_request');
        /** @var $repo \Github\Api\Organization */
        $org = $this->github->api('organization');

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

        foreach($repositories as $repository) {
            $repository['owner_login'] = $repository['owner']['login'];
            unset($repository['owner']);

            $repository['pulls'] = array();

            $pullRequests = $prApi->all($ghaccount, $repository['name']);
            $repository['open_pull_count'] = count($pullRequests);
            $repository['open_issues_only'] =
                isset($repository['open_issue_count'])
                    ? $repository['open_issue_count'] - count($pullRequests)
                    : 0;
            $docs = array();

            foreach($pullRequests as $r) {
                $r['user_login'] = $r['user']['login'];
                unset($r['user']);
                unset($r['assignee']);
                unset($r['head']);
                unset($r['base']);
                $r['_parent'] = $repository['id'];
                $docs[] = new \Elastica_Document($r['id'], $r, 'github_pull');
            }
            if ($docs) {
                $this->pullType->addDocuments($docs);
            }

            $this->repositoryType->addDocument(
                new \Elastica_Document($repository['id'], $repository, 'github_repository')
            );
        }

        return true;
    }

    private function log($message)
    {

    }
}
