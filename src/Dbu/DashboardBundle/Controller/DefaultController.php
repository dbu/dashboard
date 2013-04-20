<?php

namespace Dbu\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $q = $this->getRequest()->query->has('q')
            ? $this->getRequest()->query->get('q')
            : false;

        /** @var $index \Elastica_Index */
        $index = $this->get('fos_elastica.index.projects');
        $data = $index->search($this->buildOverviewQuery($q));

        return $this->render('DbuDashboardBundle:Default:index.html.twig', array('repositories' => $data));
    }

    public function detailsAction($id)
    {
        // TODO
    }

    private function buildOverviewQuery($q)
    {
        $query = new \Elastica_Query();

        $filterAnd = new \Elastica_Filter_And();
        $filterAnd->addFilter(new \Elastica_Filter_Type('github_repository'));
        $filterAnd->addFilter($this->buildRepositoryFilter());

        $query->setFilter($filterAnd);

        $query->setLimit(5000);

        $query->setSort(array('owner_login' => array('order' => 'asc')));

        if ($q) {
            $bool = new \Elastica_Query_Bool();

            $fuzzy = new \Elastica_Query_Fuzzy();
            $fuzzy->addField('full_name', array('value' => $q));
            $bool->addShould($fuzzy);

            $fuzzy = new \Elastica_Query_Fuzzy();
            $fuzzy->addField('description',  array('value' => $q));
            $bool->addShould($fuzzy);

            // add some fields from pull requests too
            $fuzzy = new \Elastica_Query_Fuzzy();
            $fuzzy->addField('title',  array('value' => $q));
            $nested = new \Elastica_Query_Nested();
            $nested->setPath('pulls');
            $nested->setQuery($fuzzy);
            $bool->addShould($nested);

            $fuzzy = new \Elastica_Query_Fuzzy();
            $fuzzy->addField('body',  array('value' => $q));
            $nested = new \Elastica_Query_Nested();
            $nested->setPath('pulls');
            $nested->setQuery($fuzzy);
            $bool->addShould($nested);

            $query->setQuery($bool);
        } else {
            $query->setQuery(new \Elastica_Query_MatchAll());
        }
//echo '<pre>';var_dump($query->toArray());die;
        return $query;
    }

    /**
     * @return \Elastica_Filter_Abstract
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

        $and = new \Elastica_Filter_And();
        $and->addFilter(new \Elastica_Filter_Terms('owner_login', $owners));
        $and->addFilter(new \Elastica_Filter_Not(
            new \Elastica_Filter_Terms('name', $excludes)
        ));

        $or = new \Elastica_Filter_Or();
        $or->addFilter(
          new \Elastica_Filter_Terms('full_name', $includes)
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
