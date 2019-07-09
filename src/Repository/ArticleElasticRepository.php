<?php

namespace App\Repository;


use Elastica\Query;
use Elastica\Query\QueryString;
use FOS\ElasticaBundle\Repository;

class ArticleElasticRepository extends Repository
{

    public function searchArticle($text)
    {
        $queryString = new QueryString();
        $queryString->setQuery($text);
        $queryString->setFields(array('content','title','id'));

        $query = new Query();
        $query->setQuery($queryString);

        return $this->find($queryString);

    }

}