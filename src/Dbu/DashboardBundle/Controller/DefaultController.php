<?php

namespace Dbu\DashboardBundle\Controller;

use Elastica\Filter\Term;
use Elastica\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $q = $this->getRequest()->query->has('q')
            ? $this->getRequest()->query->get('q')
            : false;

        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');
        $repositories = $index->search($this->buildOverviewQuery(false));//$q));

        return $this->render('DbuDashboardBundle:Default:index.html.twig', array(
            'repositories' => $repositories,
            'q' => $q,
        ));
    }

    public function repositoryAction($repository, $q)
    {
        /** @var $index \Elastica\Index */
        $index = $this->get('fos_elastica.index.projects');
        $info = $repository->getData();
        $pulls = $index->search($this->buildPullsQuery($info['id'], $q));

        // TODO: issues

        return $this->render('DbuDashboardBundle:Github:repository.html.twig', array(
            'repo' => $repository,
            'pulls' => $pulls->getResults(),
        ));
    }

    public function detailsAction($id)
    {
        // TODO
    }

    private function buildOverviewQuery($q)
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

            $fuzzy = new \Elastica\Query\Fuzzy();
            $fuzzy->addField('full_name', array('value' => $q));
            $bool->addShould($fuzzy);

            $fuzzy = new \Elastica\Query\Fuzzy();
            $fuzzy->addField('description',  array('value' => $q));
            $bool->addShould($fuzzy);

            // add some fields from pull requests too
            $child = new \Elastica\Query\HasChild($this->buildPullsQueryFragment($q), 'github_pull');
            $bool->addShould($child);

            $query->setQuery($bool);
        } else {
            $query->setQuery(new \Elastica\Query\MatchAll());
        }
//echo '<pre>';var_dump($query->toArray());die;
        return $query;
    }

    private function buildPullsQuery($repositoryId, $q)
    {
        $query = new Query();

        $filterRepository = new \Elastica\Filter\BoolAnd();
        $filterRepository->addFilter(new \Elastica\Filter\Type('github_pull'));
        $filterRepository->addFilter(new \Elastica\Filter\Term(array('_parent' => $repositoryId)));

        $query->setFilter($filterRepository);

        $query->setSize(5000);

        $query->setSort(array('id' => array('order' => 'desc')));

        if ($q) {
            $query->setQuery($this->buildPullsQueryFragment($q));
        } else {
            $query->setQuery(new \Elastica\Query\MatchAll());
        }

        return $query;
    }

    private function buildPullsQueryFragment($q)
    {
        $pullsBool = new \Elastica\Query\Bool();
        $fuzzy = new \Elastica\Query\Fuzzy();
        $fuzzy->addField('title',  array('value' => $q));
        $pullsBool->addShould($fuzzy);
        $fuzzy = new \Elastica\Query\Fuzzy();
        $fuzzy->addField('body',  array('value' => $q));
        $pullsBool->addShould($fuzzy);

        return $pullsBool;
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
