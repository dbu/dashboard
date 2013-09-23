<?php

namespace Dbu\GhCollectorBundle\Github;

use Github\Client;

class MockSynchronizer
{
    /** @var \Elastica\Type */
    private $repositoryType;
    /** @var \Elastica\Type */
    private $pullType;
    /** @var \Elastica\Type */
    private $issueType;

    public function __construct(
        \Elastica\Type $repositoryType,
        \Elastica\Type $pullType,
        \Elastica\Type $issueType
    ) {
        $this->repositoryType = $repositoryType;
        $this->pullType = $pullType;
        $this->issueType = $issueType;
    }

    /**
     * @param string $ghaccount github account name (user or organization)
     */
    public function synchronize($ghaccount)
    {
        $repositories = $this->getRepos();

        foreach($repositories as $name => $repository) {
            $prDocs = $this->getPulls($name);
            $issueDocs = $this->getIssues($name);

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

    private function getRepos()
    {
        return array(
            'phpcr-api-tests' => unserialize('a:68:{s:2:"id";i:248019;s:4:"name";s:15:"phpcr-api-tests";s:9:"full_name";s:21:"phpcr/phpcr-api-tests";s:7:"private";b:0;s:8:"html_url";s:40:"https://github.com/phpcr/phpcr-api-tests";s:11:"description";s:65:"Functional testing for the PHPCR API for all php implementations.";s:4:"fork";b:0;s:3:"url";s:50:"https://api.github.com/repos/phpcr/phpcr-api-tests";s:9:"forks_url";s:56:"https://api.github.com/repos/phpcr/phpcr-api-tests/forks";s:8:"keys_url";s:64:"https://api.github.com/repos/phpcr/phpcr-api-tests/keys{/key_id}";s:17:"collaborators_url";s:79:"https://api.github.com/repos/phpcr/phpcr-api-tests/collaborators{/collaborator}";s:9:"teams_url";s:56:"https://api.github.com/repos/phpcr/phpcr-api-tests/teams";s:9:"hooks_url";s:56:"https://api.github.com/repos/phpcr/phpcr-api-tests/hooks";s:16:"issue_events_url";s:73:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/events{/number}";s:10:"events_url";s:57:"https://api.github.com/repos/phpcr/phpcr-api-tests/events";s:13:"assignees_url";s:67:"https://api.github.com/repos/phpcr/phpcr-api-tests/assignees{/user}";s:12:"branches_url";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/branches{/branch}";s:8:"tags_url";s:55:"https://api.github.com/repos/phpcr/phpcr-api-tests/tags";s:9:"blobs_url";s:66:"https://api.github.com/repos/phpcr/phpcr-api-tests/git/blobs{/sha}";s:12:"git_tags_url";s:65:"https://api.github.com/repos/phpcr/phpcr-api-tests/git/tags{/sha}";s:12:"git_refs_url";s:65:"https://api.github.com/repos/phpcr/phpcr-api-tests/git/refs{/sha}";s:9:"trees_url";s:66:"https://api.github.com/repos/phpcr/phpcr-api-tests/git/trees{/sha}";s:12:"statuses_url";s:65:"https://api.github.com/repos/phpcr/phpcr-api-tests/statuses/{sha}";s:13:"languages_url";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/languages";s:14:"stargazers_url";s:61:"https://api.github.com/repos/phpcr/phpcr-api-tests/stargazers";s:16:"contributors_url";s:63:"https://api.github.com/repos/phpcr/phpcr-api-tests/contributors";s:15:"subscribers_url";s:62:"https://api.github.com/repos/phpcr/phpcr-api-tests/subscribers";s:16:"subscription_url";s:63:"https://api.github.com/repos/phpcr/phpcr-api-tests/subscription";s:11:"commits_url";s:64:"https://api.github.com/repos/phpcr/phpcr-api-tests/commits{/sha}";s:15:"git_commits_url";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/git/commits{/sha}";s:12:"comments_url";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/comments{/number}";s:17:"issue_comment_url";s:75:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/comments/{number}";s:12:"contents_url";s:67:"https://api.github.com/repos/phpcr/phpcr-api-tests/contents/{+path}";s:11:"compare_url";s:74:"https://api.github.com/repos/phpcr/phpcr-api-tests/compare/{base}...{head}";s:10:"merges_url";s:57:"https://api.github.com/repos/phpcr/phpcr-api-tests/merges";s:11:"archive_url";s:73:"https://api.github.com/repos/phpcr/phpcr-api-tests/{archive_format}{/ref}";s:13:"downloads_url";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/downloads";s:10:"issues_url";s:66:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues{/number}";s:9:"pulls_url";s:65:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls{/number}";s:14:"milestones_url";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/milestones{/number}";s:17:"notifications_url";s:90:"https://api.github.com/repos/phpcr/phpcr-api-tests/notifications{?since,all,participating}";s:10:"labels_url";s:64:"https://api.github.com/repos/phpcr/phpcr-api-tests/labels{/name}";s:10:"created_at";s:20:"2009-07-10T14:18:11Z";s:10:"updated_at";s:20:"2013-08-28T20:55:17Z";s:9:"pushed_at";s:20:"2013-08-28T20:55:12Z";s:7:"git_url";s:42:"git://github.com/phpcr/phpcr-api-tests.git";s:7:"ssh_url";s:40:"git@github.com:phpcr/phpcr-api-tests.git";s:9:"clone_url";s:44:"https://github.com/phpcr/phpcr-api-tests.git";s:7:"svn_url";s:40:"https://github.com/phpcr/phpcr-api-tests";s:8:"homepage";s:22:"http://phpcr.github.io";s:4:"size";i:10225;s:14:"watchers_count";i:19;s:8:"language";s:3:"PHP";s:10:"has_issues";b:1;s:13:"has_downloads";b:1;s:8:"has_wiki";b:0;s:11:"forks_count";i:18;s:10:"mirror_url";N;s:17:"open_issues_count";i:12;s:5:"forks";i:18;s:11:"open_issues";i:12;s:8:"watchers";i:19;s:13:"master_branch";s:6:"master";s:14:"default_branch";s:6:"master";s:11:"permissions";a:3:{s:5:"admin";b:1;s:4:"push";b:1;s:4:"pull";b:1;}s:11:"owner_login";s:5:"phpcr";s:15:"open_pull_count";i:5;s:16:"open_issues_only";i:7;}'),
            'phpcr' => unserialize('a:68:{s:2:"id";i:182652;s:4:"name";s:5:"phpcr";s:9:"full_name";s:11:"phpcr/phpcr";s:7:"private";b:0;s:8:"html_url";s:30:"https://github.com/phpcr/phpcr";s:11:"description";s:49:"Port of the Java Content Repository (JCR) to PHP.";s:4:"fork";b:0;s:3:"url";s:40:"https://api.github.com/repos/phpcr/phpcr";s:9:"forks_url";s:46:"https://api.github.com/repos/phpcr/phpcr/forks";s:8:"keys_url";s:54:"https://api.github.com/repos/phpcr/phpcr/keys{/key_id}";s:17:"collaborators_url";s:69:"https://api.github.com/repos/phpcr/phpcr/collaborators{/collaborator}";s:9:"teams_url";s:46:"https://api.github.com/repos/phpcr/phpcr/teams";s:9:"hooks_url";s:46:"https://api.github.com/repos/phpcr/phpcr/hooks";s:16:"issue_events_url";s:63:"https://api.github.com/repos/phpcr/phpcr/issues/events{/number}";s:10:"events_url";s:47:"https://api.github.com/repos/phpcr/phpcr/events";s:13:"assignees_url";s:57:"https://api.github.com/repos/phpcr/phpcr/assignees{/user}";s:12:"branches_url";s:58:"https://api.github.com/repos/phpcr/phpcr/branches{/branch}";s:8:"tags_url";s:45:"https://api.github.com/repos/phpcr/phpcr/tags";s:9:"blobs_url";s:56:"https://api.github.com/repos/phpcr/phpcr/git/blobs{/sha}";s:12:"git_tags_url";s:55:"https://api.github.com/repos/phpcr/phpcr/git/tags{/sha}";s:12:"git_refs_url";s:55:"https://api.github.com/repos/phpcr/phpcr/git/refs{/sha}";s:9:"trees_url";s:56:"https://api.github.com/repos/phpcr/phpcr/git/trees{/sha}";s:12:"statuses_url";s:55:"https://api.github.com/repos/phpcr/phpcr/statuses/{sha}";s:13:"languages_url";s:50:"https://api.github.com/repos/phpcr/phpcr/languages";s:14:"stargazers_url";s:51:"https://api.github.com/repos/phpcr/phpcr/stargazers";s:16:"contributors_url";s:53:"https://api.github.com/repos/phpcr/phpcr/contributors";s:15:"subscribers_url";s:52:"https://api.github.com/repos/phpcr/phpcr/subscribers";s:16:"subscription_url";s:53:"https://api.github.com/repos/phpcr/phpcr/subscription";s:11:"commits_url";s:54:"https://api.github.com/repos/phpcr/phpcr/commits{/sha}";s:15:"git_commits_url";s:58:"https://api.github.com/repos/phpcr/phpcr/git/commits{/sha}";s:12:"comments_url";s:58:"https://api.github.com/repos/phpcr/phpcr/comments{/number}";s:17:"issue_comment_url";s:65:"https://api.github.com/repos/phpcr/phpcr/issues/comments/{number}";s:12:"contents_url";s:57:"https://api.github.com/repos/phpcr/phpcr/contents/{+path}";s:11:"compare_url";s:64:"https://api.github.com/repos/phpcr/phpcr/compare/{base}...{head}";s:10:"merges_url";s:47:"https://api.github.com/repos/phpcr/phpcr/merges";s:11:"archive_url";s:63:"https://api.github.com/repos/phpcr/phpcr/{archive_format}{/ref}";s:13:"downloads_url";s:50:"https://api.github.com/repos/phpcr/phpcr/downloads";s:10:"issues_url";s:56:"https://api.github.com/repos/phpcr/phpcr/issues{/number}";s:9:"pulls_url";s:55:"https://api.github.com/repos/phpcr/phpcr/pulls{/number}";s:14:"milestones_url";s:60:"https://api.github.com/repos/phpcr/phpcr/milestones{/number}";s:17:"notifications_url";s:80:"https://api.github.com/repos/phpcr/phpcr/notifications{?since,all,participating}";s:10:"labels_url";s:54:"https://api.github.com/repos/phpcr/phpcr/labels{/name}";s:10:"created_at";s:20:"2009-04-22T13:09:11Z";s:10:"updated_at";s:20:"2013-09-16T02:38:43Z";s:9:"pushed_at";s:20:"2013-09-02T13:54:49Z";s:7:"git_url";s:32:"git://github.com/phpcr/phpcr.git";s:7:"ssh_url";s:30:"git@github.com:phpcr/phpcr.git";s:9:"clone_url";s:34:"https://github.com/phpcr/phpcr.git";s:7:"svn_url";s:30:"https://github.com/phpcr/phpcr";s:8:"homepage";s:22:"http://phpcr.github.io";s:4:"size";i:580;s:14:"watchers_count";i:259;s:8:"language";s:3:"PHP";s:10:"has_issues";b:1;s:13:"has_downloads";b:1;s:8:"has_wiki";b:0;s:11:"forks_count";i:25;s:10:"mirror_url";N;s:17:"open_issues_count";i:2;s:5:"forks";i:25;s:11:"open_issues";i:2;s:8:"watchers";i:259;s:13:"master_branch";s:6:"master";s:14:"default_branch";s:6:"master";s:11:"permissions";a:3:{s:5:"admin";b:1;s:4:"push";b:1;s:4:"pull";b:1;}s:11:"owner_login";s:5:"phpcr";s:15:"open_pull_count";i:1;s:16:"open_issues_only";i:1;}'),
        );
    }

    private function getPulls($repo)
    {
        switch($repo) {
            case 'phpcr':
                return array(
                    new \Elastica\Document(18043197, unserialize('a:19:{s:3:"url";s:50:"https://api.github.com/repos/phpcr/phpcr/issues/71";s:10:"labels_url";s:64:"https://api.github.com/repos/phpcr/phpcr/issues/71/labels{/name}";s:12:"comments_url";s:59:"https://api.github.com/repos/phpcr/phpcr/issues/71/comments";s:10:"events_url";s:57:"https://api.github.com/repos/phpcr/phpcr/issues/71/events";s:8:"html_url";s:40:"https://github.com/phpcr/phpcr/issues/71";s:2:"id";i:18043197;s:6:"number";i:71;s:5:"title";s:47:"return hashmap of definitions instead of array?";s:6:"labels";a:0:{}s:5:"state";s:4:"open";s:8:"comments";i:0;s:10:"created_at";s:20:"2013-08-14T09:39:05Z";s:10:"updated_at";s:20:"2013-08-14T10:21:29Z";s:9:"closed_at";N;s:12:"pull_request";a:3:{s:8:"html_url";N;s:8:"diff_url";N;s:9:"patch_url";N;}s:4:"body";s:231:"NodeTypeInterface::getPropertyDefinitions returns a plain array. when you look for the definition of a specific property, a hashmap would be more useful. can we change to that? can there ever be 2 definitions for the same property?";s:10:"user_login";s:3:"dbu";s:14:"assignee_login";b:0;s:7:"_parent";i:182652;}'), 'github_pull'),
                );
            case 'phpcr-api-tests':
                return array(
                    new \Elastica\Document(15930031, unserialize('a:19:{s:3:"url";s:61:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/110";s:10:"labels_url";s:75:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/110/labels{/name}";s:12:"comments_url";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/110/comments";s:10:"events_url";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/110/events";s:8:"html_url";s:51:"https://github.com/phpcr/phpcr-api-tests/issues/110";s:2:"id";i:15930031;s:6:"number";i:110;s:5:"title";s:49:"add test for modifying a protected property value";s:6:"labels";a:0:{}s:5:"state";s:4:"open";s:8:"comments";i:0;s:10:"created_at";s:20:"2013-06-24T15:17:06Z";s:10:"updated_at";s:20:"2013-06-24T15:17:06Z";s:9:"closed_at";N;s:12:"pull_request";a:3:{s:8:"html_url";N;s:8:"diff_url";N;s:9:"patch_url";N;}s:4:"body";s:51:"see https://github.com/jackalope/jackalope/pull/170";s:10:"user_login";s:8:"lsmith77";s:14:"assignee_login";b:0;s:7:"_parent";i:248019;}'), 'github_pull'),
                    new \Elastica\Document(15929993, unserialize('a:19:{s:3:"url";s:61:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/109";s:10:"labels_url";s:75:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/109/labels{/name}";s:12:"comments_url";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/109/comments";s:10:"events_url";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/109/events";s:8:"html_url";s:51:"https://github.com/phpcr/phpcr-api-tests/issues/109";s:2:"id";i:15929993;s:6:"number";i:109;s:5:"title";s:29:"add tests for SQL2 CONTAINS()";s:6:"labels";a:0:{}s:5:"state";s:4:"open";s:8:"comments";i:0;s:10:"created_at";s:20:"2013-06-24T15:16:27Z";s:10:"updated_at";s:20:"2013-06-24T15:17:49Z";s:9:"closed_at";N;s:12:"pull_request";a:3:{s:8:"html_url";N;s:8:"diff_url";N;s:9:"patch_url";N;}s:4:"body";s:0:"";s:10:"user_login";s:8:"lsmith77";s:14:"assignee_login";b:0;s:7:"_parent";i:248019;}'), 'github_pull'),
                    new \Elastica\Document(15031036, unserialize('a:19:{s:3:"url";s:61:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/104";s:10:"labels_url";s:75:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/104/labels{/name}";s:12:"comments_url";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/104/comments";s:10:"events_url";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/104/events";s:8:"html_url";s:51:"https://github.com/phpcr/phpcr-api-tests/issues/104";s:2:"id";i:15031036;s:6:"number";i:104;s:5:"title";s:86:"add a test to check if a specific timezone is maintained on a store/retrieve roundtrip";s:6:"labels";a:0:{}s:5:"state";s:4:"open";s:8:"comments";i:0;s:10:"created_at";s:20:"2013-06-01T18:50:28Z";s:10:"updated_at";s:20:"2013-06-01T18:50:28Z";s:9:"closed_at";N;s:12:"pull_request";a:3:{s:8:"html_url";N;s:8:"diff_url";N;s:9:"patch_url";N;}s:4:"body";s:80:"note Jackalope Doctrine DBAL does not support this atm, Jackrabbit does however.";s:10:"user_login";s:8:"lsmith77";s:14:"assignee_login";b:0;s:7:"_parent";i:248019;}'), 'github_pull'),
                );
        }
    }

    private function getIssues($repo)
    {
        switch($repo) {
            case 'phpcr':
                return array(
                    new \Elastica\Document(8021995, array(
                        'url' => "https://api.github.com/repos/phpcr/phpcr/pulls/72",
                        'id' => 8021995,
                        "html_url" => "https://github.com/phpcr/phpcr/pull/72",
                        "issue_url" => "https://github.com/phpcr/phpcr/pull/72",
                        "number" => 72,
                        "state" => "open",
                        "title" => "adjust to https://java.net/jira/browse/JSR_333-71",
                        "body" => "there where a few inconsistencies in the doc, leading to misinterpretation. i will try to do a PR for that soon.",
                        "created_at" => "2013-09-02T14:02:04Z",
                        "updated_at" => "2013-09-02T14:02:04Z",
                        "closed_at" => null,
                        "merged_at" => null,
                        "merge_commit_sha" => "ca1bf88ff0507c3ed5b84377413952febc65ed43",
                        "user_login" => "dbu",
                        "assignee_login" => false,
                        "_parent" => 182652,
                        "milestone_title" => '1.0',
                    ), 'github_issue'));
            case 'phpcr-api-tests':
                return array(
                    new \Elastica\Document(7159338, unserialize('a:23:{s:3:"url";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/115";s:2:"id";i:7159338;s:8:"html_url";s:49:"https://github.com/phpcr/phpcr-api-tests/pull/115";s:8:"diff_url";s:54:"https://github.com/phpcr/phpcr-api-tests/pull/115.diff";s:9:"patch_url";s:55:"https://github.com/phpcr/phpcr-api-tests/pull/115.patch";s:9:"issue_url";s:49:"https://github.com/phpcr/phpcr-api-tests/pull/115";s:6:"number";i:115;s:5:"state";s:4:"open";s:5:"title";s:31:"qom always uses a selector name";s:4:"body";s:48:"adjust to https://github.com/phpcr/phpcr/pull/68";s:10:"created_at";s:20:"2013-07-25T07:11:23Z";s:10:"updated_at";s:20:"2013-07-25T09:24:43Z";s:9:"closed_at";N;s:9:"merged_at";N;s:16:"merge_commit_sha";s:40:"e6ba5dc20ec3c0f843fca4a6ed22b848a3ca39d6";s:11:"commits_url";s:57:"https://github.com/phpcr/phpcr-api-tests/pull/115/commits";s:19:"review_comments_url";s:58:"https://github.com/phpcr/phpcr-api-tests/pull/115/comments";s:18:"review_comment_url";s:52:"/repos/phpcr/phpcr-api-tests/pulls/comments/{number}";s:12:"comments_url";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/115/comments";s:6:"_links";a:5:{s:4:"self";a:1:{s:4:"href";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/115";}s:4:"html";a:1:{s:4:"href";s:49:"https://github.com/phpcr/phpcr-api-tests/pull/115";}s:5:"issue";a:1:{s:4:"href";s:61:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/115";}s:8:"comments";a:1:{s:4:"href";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/115/comments";}s:15:"review_comments";a:1:{s:4:"href";s:69:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/115/comments";}}s:10:"user_login";s:3:"dbu";s:14:"assignee_login";b:0;s:7:"_parent";i:248019;}'), 'github_issue'),
                    new \Elastica\Document(5486086, unserialize('a:23:{s:3:"url";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/100";s:2:"id";i:5486086;s:8:"html_url";s:49:"https://github.com/phpcr/phpcr-api-tests/pull/100";s:8:"diff_url";s:54:"https://github.com/phpcr/phpcr-api-tests/pull/100.diff";s:9:"patch_url";s:55:"https://github.com/phpcr/phpcr-api-tests/pull/100.patch";s:9:"issue_url";s:49:"https://github.com/phpcr/phpcr-api-tests/pull/100";s:6:"number";i:100;s:5:"state";s:4:"open";s:5:"title";s:61:"WIP have import tests not import to root but to /tests_import";s:4:"body";s:115:"this currently triggers a jackrabbit bug and brings the repository into a failed state. need to investigate further";s:10:"created_at";s:20:"2013-05-02T05:49:01Z";s:10:"updated_at";s:20:"2013-05-02T05:49:01Z";s:9:"closed_at";N;s:9:"merged_at";N;s:16:"merge_commit_sha";s:40:"9e936791405e2a972163a22d188d757c7fd772c9";s:11:"commits_url";s:57:"https://github.com/phpcr/phpcr-api-tests/pull/100/commits";s:19:"review_comments_url";s:58:"https://github.com/phpcr/phpcr-api-tests/pull/100/comments";s:18:"review_comment_url";s:52:"/repos/phpcr/phpcr-api-tests/pulls/comments/{number}";s:12:"comments_url";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/100/comments";s:6:"_links";a:5:{s:4:"self";a:1:{s:4:"href";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/100";}s:4:"html";a:1:{s:4:"href";s:49:"https://github.com/phpcr/phpcr-api-tests/pull/100";}s:5:"issue";a:1:{s:4:"href";s:61:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/100";}s:8:"comments";a:1:{s:4:"href";s:70:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/100/comments";}s:15:"review_comments";a:1:{s:4:"href";s:69:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/100/comments";}}s:10:"user_login";s:3:"dbu";s:14:"assignee_login";b:0;s:7:"_parent";i:248019;}'), 'github_issue'),
                    new \Elastica\Document(5160719, unserialize('a:23:{s:3:"url";s:59:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/95";s:2:"id";i:5160719;s:8:"html_url";s:48:"https://github.com/phpcr/phpcr-api-tests/pull/95";s:8:"diff_url";s:53:"https://github.com/phpcr/phpcr-api-tests/pull/95.diff";s:9:"patch_url";s:54:"https://github.com/phpcr/phpcr-api-tests/pull/95.patch";s:9:"issue_url";s:48:"https://github.com/phpcr/phpcr-api-tests/pull/95";s:6:"number";i:95;s:5:"state";s:4:"open";s:5:"title";s:51:"[WIP] Added test for DateTime objectrs in QOM query";s:4:"body";s:128:"Not really sure how this works. .. obviously the aim is to test to see that a DateTime object is accepted by the implementation.";s:10:"created_at";s:20:"2013-04-15T11:56:53Z";s:10:"updated_at";s:20:"2013-06-09T10:52:46Z";s:9:"closed_at";N;s:9:"merged_at";N;s:16:"merge_commit_sha";s:40:"0d2e21606694d625ceff830e8a5f5acce971f19d";s:11:"commits_url";s:56:"https://github.com/phpcr/phpcr-api-tests/pull/95/commits";s:19:"review_comments_url";s:57:"https://github.com/phpcr/phpcr-api-tests/pull/95/comments";s:18:"review_comment_url";s:52:"/repos/phpcr/phpcr-api-tests/pulls/comments/{number}";s:12:"comments_url";s:69:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/95/comments";s:6:"_links";a:5:{s:4:"self";a:1:{s:4:"href";s:59:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/95";}s:4:"html";a:1:{s:4:"href";s:48:"https://github.com/phpcr/phpcr-api-tests/pull/95";}s:5:"issue";a:1:{s:4:"href";s:60:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/95";}s:8:"comments";a:1:{s:4:"href";s:69:"https://api.github.com/repos/phpcr/phpcr-api-tests/issues/95/comments";}s:15:"review_comments";a:1:{s:4:"href";s:68:"https://api.github.com/repos/phpcr/phpcr-api-tests/pulls/95/comments";}}s:10:"user_login";s:9:"dantleech";s:14:"assignee_login";b:0;s:7:"_parent";i:248019;}'), 'github_issue'),
                );
        }
    }
}
