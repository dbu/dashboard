<?php

namespace Dbu\DashboardBundle\Model;

interface Questionary
{
    /**
     * @return Question[]
     */
    public function getQuestions();
    public function getHeaders();
}