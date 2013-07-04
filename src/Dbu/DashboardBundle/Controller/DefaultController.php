<?php

namespace Dbu\DashboardBundle\Controller;

use Elastica\Filter\Bool;
use Elastica\Filter\HasParent;
use Elastica\Filter\Term;
use Elastica\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $q = $request->query->has('q')
            ? $request->query->get('q')
            : false
        ;
        $facets = $request->query->has('facets')
            ? $request->query->get('facets')
            : array()
        ;
        $lastupdated = $request->query->has('lastupdated')
            ? (int) $request->query->get('lastupdated')
            : false
        ;
        $created = $request->query->has('created')
            ? (int) $request->query->get('created')
            : false
        ;

        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');
        $repositories = $index->search($this->buildOverviewQuery(false /*$q*/, $facets, $lastupdated, $created));

        return $this->render('DbuDashboardBundle:Default:index.html.twig', array(
            'repositories' => $repositories,
            'q' => $q,
            'facets' => $facets,
            'lastupdated' => $lastupdated,
            'created' => $created,
        ));
    }

    public function repositoryAction($repository, $q, array $facets, $lastupdated, $created)
    {
        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');
        $info = $repository->getData();
        $pulls = $index->search($this->buildIssuesQuery($info['id'], $q, $facets, $lastupdated, $created, 'github_pull'));
        $issues = $index->search($this->buildIssuesQuery($info['id'], $q, $facets, $lastupdated, $created, 'github_issue'));

        // workaround for missing children query
        if ($q && ! (count($pulls) || count($issues))) {
            return new Response();
        }

        return $this->render('DbuDashboardBundle:Github:repository.html.twig', array(
            'repo' => $repository,
            'pulls' => $pulls->getResults(),
            'issues' => $issues->getResults(),
        ));
    }

    private function buildOverviewQuery($q, array $facets, $lastupdated, $created)
    {
        $queryObject = new Query();

        $queryObject->setSize(5000);

        $queryObject->setSort(array('owner_login' => array('order' => 'asc')));

        if ($q) {
            // either the name, description or one of the children need to match
            $query = new \Elastica\Query\Bool();

            $fuzzy = new \Elastica\Query\Fuzzy('full_name', $q);
            $query->addShould($fuzzy);

            $fuzzy = new \Elastica\Query\Fuzzy('description', $q);
            $query->addShould($fuzzy);

            // add pull requests and issues too
            $child = new \Elastica\Query\HasChild($this->buildIssuesQueryFragment($q), 'github_pull');
            $query->addShould($child);
            $child = new \Elastica\Query\HasChild($this->buildIssuesQueryFragment($q), 'github_issue');
            $query->addShould($child);
        } else {
            $query = new \Elastica\Query\MatchAll();
        }

        $repositoryFilter = $this->buildRepositoryFilter();
        $parentFilter = new HasParent(
            new Query\Filtered(new \Elastica\Query\MatchAll(), $repositoryFilter),
            'github_repository'
        );

        $facet = new \Elastica\Facet\Terms('Milestones');
        $facet->setField('milestone_title');
        $facet->setSize(3);
        $facet->setOrder('count');
        //$facet->setFilter($parentFilter);
        $queryObject->addFacet($facet);

        $facet = new \Elastica\Facet\Terms('Author');
        $facet->setField('user_login');
        $facet->setSize(10);
        $facet->setOrder('count');
        //$facet->setFilter($parentFilter);
        $queryObject->addFacet($facet);

        $facet = new \Elastica\Facet\Terms('Assignee');
        $facet->setField('assignee_login');
        $facet->setSize(10);
        $facet->setOrder('count');
        //$facet->setFilter($parentFilter);
        $queryObject->addFacet($facet);

        $filterAnd = new \Elastica\Filter\BoolAnd();
        $filterAnd->addFilter($repositoryFilter);
        $filterAnd->addFilter(new \Elastica\Filter\Type('github_repository'));

        $queryObject->setQuery($query);
        $queryObject->setFilter($filterAnd);

//echo '<pre>';var_dump(json_encode($queryObject->toArray(), 128));die;
        return $queryObject;
    }

    /**
     * Query for github issues or pulls
     * @param $repositoryId
     * @param $q
     * @param array $facets list of facet filters to use
     * @param string $type
     *
     * @return Query
     */
    private function buildIssuesQuery($repositoryId, $q, array $facets, $lastupdated, $created, $type)
    {
        $query = new Query();

        $filterRepository = new \Elastica\Filter\BoolAnd();
        $filterRepository->addFilter(new \Elastica\Filter\Type($type));
        $filterRepository->addFilter(new \Elastica\Filter\Term(array('_parent' => $repositoryId)));
        if (isset($facets['Milestones'])) {
            $filterRepository->addFilter(new \Elastica\Filter\Terms('milestone_title', $facets['Milestones']));
        }
        if (isset($facets['Author'])) {
            $filterRepository->addFilter(new \Elastica\Filter\Terms('user_login', $facets['Author']));
        }
        if (isset($facets['Assignee'])) {
            $filterRepository->addFilter(new \Elastica\Filter\Terms('assignee_login', $facets['Assignee']));
        }
        if ($lastupdated) {
            $filterRepository->addFilter(new \Elastica\Filter\NumericRange('updated_at', array('from' => date('Y-m-d', strtotime("{$lastupdated} days ago")))));
        }
        if ($created) {
            $filterRepository->addFilter(new \Elastica\Filter\NumericRange('created_at', array('from' => date('Y-m-d', strtotime("{$created} days ago")))));
        }

        $query->setFilter($filterRepository);
//echo '<pre>';var_dump($query->toArray());die;
        $query->setSize(5000);

        $query->setSort(array('id' => array('order' => 'desc')));

        if ($q) {
            $query->setQuery($this->buildIssuesQueryFragment($q));
        } else {
            $query->setQuery(new \Elastica\Query\MatchAll());
        }

        return $query;
    }

    private function buildIssuesQueryFragment($q)
    {
        $issuesBool = new \Elastica\Query\Bool();
        $fuzzy = new \Elastica\Query\Fuzzy('title', $q);
        $issuesBool->addShould($fuzzy);
        $fuzzy = new \Elastica\Query\Fuzzy('body', $q);
        $issuesBool->addShould($fuzzy);

        return $issuesBool;
    }

    /**
     * @return \Elastica\Filter\AbstractFilter
     */
    private function buildRepositoryFilter()
    {
        $owners = array();
        $excludes = array();
        $includes = array();
        foreach ($this->getRepositories() as $repository => $options) {

            if (isset($options['include'])) {
                foreach ($options['include'] as $include) {
                    $includes[] = "$repository/$include";
                }
            } else {
                $owners[] = $repository;

                if (isset($options['exclude'])) {
                    foreach ($options['exclude'] as $exclude) {
                        $excludes[] = $exclude;//"$repository/$exclude";
                    }
                }
            }
        }

        $bool = new Bool();
        $bool->addMust(new \Elastica\Filter\Terms('owner_login', $owners));
        $bool->addMustNot(new \Elastica\Filter\Terms('name', $excludes));

        $or = new \Elastica\Filter\BoolOr();
        $or->addFilter(
          new \Elastica\Filter\Terms('full_name', $includes)
        );
        $or->addFilter($bool);

        return $or;
    }

    /**
     * Repository configuration array
     *
     * @return array
     */
    private function getRepositories()
    {
        return $this->container->getParameter('repositories');
    }

    private function debug($thing)
    {
        var_dump(json_encode($thing->toArray(), 128));die;
    }
}
