<?php
/**
 * Created by PhpStorm.
 * User: oralcinar
 * Date: 2019-07-09
 * Time: 01:53
 */

namespace App\Service\Article;


use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MultiMatch;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ArticleService
{

    /** @var \Redis */
    private $redis;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct($sncRedisDefault,ContainerInterface $container)
    {
        $this->redis = $sncRedisDefault;
        $this->container = $container;
    }

    /**
     * @param array $params
     * @param Request $request
     * @return array
     */
    public function getDocument(array $params,Request $request)
    {
        $finder = $this->container->get('fos_elastica.finder.app.article');
        $results = $finder->find('title');
        var_dump($results);
        exit;
        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);

        $match = new MultiMatch();
        $match->setQuery($query);
        $match->setFields(["title^4", "summary", "content", "author"]);

        $bool = new BoolQuery();
        $bool->addMust($match);

        $elasticaQuery = new Query($bool);
        $elasticaQuery->setSize($limit);


        $foundPosts = $finder->get('fos_elastica.finder.app.article')->search($elasticaQuery);
        $results = [];
        foreach ($foundPosts as $post) {
            $results[] = $post->getSource();
        }




        exit;


        return $results;

    }


}