<?php

namespace App\Repository;


use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use FOS\ElasticaBundle\Repository;

class ArticleElasticRepository extends Repository
{


    public function searchArticle(string $text)
    {

        $query = new BoolQuery();
        $query->addMust(new Terms('title', [$text]));
        $query = Query::create($query);

        return $this->find($query);
    }
}