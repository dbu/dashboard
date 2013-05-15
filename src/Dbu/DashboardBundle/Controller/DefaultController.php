<?php

namespace Dbu\DashboardBundle\Controller;

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
        $maxage = $request->query->has('maxage')
            ? (int) $request->query->get('maxage')
            : false
        ;

        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');
        $repositories = $index->search($this->buildOverviewQuery(false /*$q*/, $facets, $maxage));

        return $this->render('DbuDashboardBundle:Default:index.html.twig', array(
            'repositories' => $repositories,
            'q' => $q,
            'facets' => $facets,
            'maxage' => $maxage,
        ));
    }

    public function repositoryAction($repository, $q, array $facets, $maxage)
    {
        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');
        $info = $repository->getData();
        $pulls = $index->search($this->buildIssuesQuery($info['id'], $q, $facets, $maxage, 'github_pull'));
        $issues = $index->search($this->buildIssuesQuery($info['id'], $q, $facets, $maxage, 'github_issue'));

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

    private function buildOverviewQuery($q, array $facets, $maxage)
    {
        $query = new Query();

        $filterAnd = new \Elastica\Filter\BoolAnd();
        $filterAnd->addFilter(new \Elastica\Filter\Type('github_repository'));
        $filterAnd->addFilter($this->buildRepositoryFilter());

        $query->setFilter($filterAnd);

        $query->setSize(5000);

        $query->setSort(array('owner_login' => array('order' => 'asc')));

        if ($q) {
            $bool = new \Elastica\Query\Bool();

            $fuzzy = new \Elastica\Query\Fuzzy('full_name', $q);
            $bool->addShould($fuzzy);

            $fuzzy = new \Elastica\Query\Fuzzy('description', $q);
            $bool->addShould($fuzzy);

            /*
             * TODO: add facet on children. can we add this to buildIssuesQueryFragment?
            if (isset($facets['Milestones'])) {

                $filter->addFilter(new \Elastica\Filter\Terms('milestone_title', $facets['Milestones']));
            }
            */
            // TODO: same about $maxage

            // add pull requests and issues too
            $child = new \Elastica\Query\HasChild($this->buildIssuesQueryFragment($q), 'github_pull');
            $bool->addShould($child);
            $child = new \Elastica\Query\HasChild($this->buildIssuesQueryFragment($q), 'github_issue');
            $bool->addShould($child);

            $query->setQuery($bool);
        } else {
            $query->setQuery(new \Elastica\Query\MatchAll());
        }

        $facet = new \Elastica\Facet\Terms('Milestones');
        $facet->setField('milestone_title');
        $facet->setSize(10);
        $facet->setOrder('reverse_count');

        // Add that facet to the search query object.
        $query->addFacet($facet);

//echo '<pre>';var_dump($query->toArray());die;
        return $query;
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
    private function buildIssuesQuery($repositoryId, $q, array $facets, $maxage, $type)
    {
        $query = new Query();

        $filterRepository = new \Elastica\Filter\BoolAnd();
        $filterRepository->addFilter(new \Elastica\Filter\Type($type));
        $filterRepository->addFilter(new \Elastica\Filter\Term(array('_parent' => $repositoryId)));
        if (isset($facets['Milestones'])) {
            $filterRepository->addFilter(new \Elastica\Filter\Terms('milestone_title', $facets['Milestones']));
        }
        if ($maxage) {
            //TODO: fix
            //$filterRepository->addFilter(new \Elastica\Filter\NumericRange('updated_at', array('from' => new \DateTime)));
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

        $and = new \Elastica\Filter\BoolAnd();
        $and->addFilter(new \Elastica\Filter\Terms('owner_login', $owners));
        $and->addFilter(new \Elastica\Filter\BoolNot(
            new \Elastica\Filter\Terms('name', $excludes)
        ));

        $or = new \Elastica\Filter\BoolOr();
        $or->addFilter(
          new \Elastica\Filter\Terms('full_name', $includes)
        );
        $or->addFilter($and);
//echo '<pre>';var_dump($or->toArray());die;
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
}
