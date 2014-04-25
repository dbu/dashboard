<?php

namespace Dbu\DashboardBundle\Controller;

use Elastica\Filter\Bool;
use Elastica\Filter\BoolAnd;
use Elastica\Query;
use Elastica\Result;
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
        $activeFacets = $request->query->has('facets')
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

        /** @var $index \Elastica\Type */
        $repositoryType = $this->get('fos_elastica.index.projects.github_repository');
        $repositories = $repositoryType->search($this->buildOverviewQuery(false /*$q*/, $activeFacets, $lastupdated, $created));

        $facets = $this->buildFacets($activeFacets, $lastupdated, $created);

        return $this->render('DbuDashboardBundle:Default:index.html.twig', array(
            'repositories' => $repositories,
            'q' => $q,
            'facets' => $facets,
            'active_facets' => $activeFacets,
            'lastupdated' => $lastupdated,
            'created' => $created,
        ));
    }

    /**
     * Render one repository.
     *
     * @param $repository
     * @param $q
     * @param array $activeFacets
     * @param $lastupdated
     * @param $created
     *
     * @return Response
     */
    public function repositoryAction(Result $repository, $q, array $activeFacets, $lastupdated, $created)
    {
        /** @var $pullType \Elastica\Type */
        $pullType = $this->get('fos_elastica.index.projects.github_pull');
        /** @var $issueType \Elastica\Type */
        $issueType = $this->get('fos_elastica.index.projects.github_issue');

        $info = $repository->getData();
        $query = $this->buildIssuesQuery($info['id'], $q, $activeFacets, $lastupdated, $created);

        $pulls = $pullType->search($query);
        $issues = $issueType->search($query);

        if (($q || count($activeFacets || $lastupdated || $created))
            && ! (count($pulls) || count($issues))) {
            return $this->render('DbuDashboardBundle:Github:emptyRepository.html.twig', array(
                'repo' => $repository,
            ));
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
            // either the name, description or one of the children needs to match
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

        $queryObject->setQuery($query);

        $queryObject->setFilter($this->buildRepositoryFilter());

        return $queryObject;
    }

    private function buildFacets(array $activeFacets, $lastupdated, $created)
    {
        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');

        $query = new Query();
        $query->setSize(0);

        $facet = new \Elastica\Facet\Terms('Milestones');
        $facet->setField('milestone_title');
        $facet->setSize(3);
        $facet->setOrder('count');
        $query->addFacet($facet);

        $facet = new \Elastica\Facet\Terms('Author');
        $facet->setField('user_login');
        $facet->setSize(8);
        $facet->setOrder('count');
        $query->addFacet($facet);

        $facet = new \Elastica\Facet\Terms('Assignee');
        $facet->setField('assignee_login');
        $facet->setSize(8);
        $facet->setOrder('count');
        $query->addFacet($facet);

        $filter = new BoolAnd();
        $filter->addFilter($this->buildRepositoryFilter());
        $facetFilter = $this->buildActiveFacetsFilter($activeFacets, $lastupdated, $created);
        if ($facetFilter->getFilters()) {
            $filter->addFilter($facetFilter);
        }
        $query->setQuery(new Query\Filtered(new \Elastica\Query\MatchAll(), $filter));

        return $index->search($query)->getFacets();
    }

    /**
     * Query for github issues or pulls.
     *
     * @param $repositoryId
     * @param string $q Query string
     * @param array $activeFacets list of facet filters to use
     * @param $lastupdated
     * @param $created
     *
     * @return Query
     */
    private function buildIssuesQuery($repositoryId, $q, array $activeFacets, $lastupdated, $created)
    {
        $query = new Query();

        $bool = new Query\Bool();

        $bool->addMust(new \Elastica\Query\Term(array('_parent' => $repositoryId)));

        $activeFacetsFilter = $this->buildActiveFacetsFilter($activeFacets, $lastupdated, $created);
        if ($activeFacetsFilter->getFilters()) {
            $query->setFilter($activeFacetsFilter);
        }
        $query->setSize(5000);
        $query->setSort(array('id' => array('order' => 'desc')));

        if ($q) {
            $bool->addMust($this->buildIssuesQueryFragment($q));
        }

        $query->setQuery($bool);

        return $query;
    }

    /**
     * @param array $activeFacets
     * @param $lastupdated
     * @param $created
     *
     * @return \Elastica\Filter\AbstractMulti the filter to apply
     */
    private function buildActiveFacetsFilter(array $activeFacets, $lastupdated, $created)
    {
        $activeFacetsFilter = new \Elastica\Filter\BoolAnd();
        if (isset($activeFacets['Milestones'])) {
            $activeFacetsFilter->addFilter(new \Elastica\Filter\Terms('milestone_title', $activeFacets['Milestones']));
        }
        if (isset($activeFacets['Author'])) {
            $activeFacetsFilter->addFilter(new \Elastica\Filter\Terms('user_login', $activeFacets['Author']));
        }
        if (isset($activeFacets['Assignee'])) {
            $activeFacetsFilter->addFilter(new \Elastica\Filter\Terms('assignee_login', $activeFacets['Assignee']));
        }
        if ($lastupdated) {
            $activeFacetsFilter->addFilter(new \Elastica\Filter\NumericRange('updated_at', array('from' => date('Y-m-d', strtotime("{$lastupdated} days ago")))));
        }
        if ($created) {
            $activeFacetsFilter->addFilter(new \Elastica\Filter\NumericRange('created_at', array('from' => date('Y-m-d', strtotime("{$created} days ago")))));
        }

        return $activeFacetsFilter;
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
                        $excludes[] = "$repository/$exclude";
                    }
                }
            }
        }

        $filter = new \Elastica\Filter\Terms('owner_login', $owners);

        if ($excludes) {
            $prev = $filter;
            $filter = new Bool();
            $filter->addMust($prev);
            $filter->addMustNot(new \Elastica\Filter\Terms('full_name', $excludes));
        }

        if ($includes) {
            $prev = $filter;
            $filter = new \Elastica\Filter\BoolOr();
            $filter->addFilter($prev);
            $filter->addFilter(
                new \Elastica\Filter\Terms('full_name', $includes)
            );
        }

        return $filter;
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
}
