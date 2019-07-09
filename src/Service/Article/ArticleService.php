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

    public function __construct($sncRedisDefault, ContainerInterface $container)
    {
        $this->redis = $sncRedisDefault;
        $this->container = $container;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getDocument(array $params)
    {

        /** var FOS\ElasticaBundle\Manager\RepositoryManager */
        $repositoryManager = $this->container->get('fos_elastica.manager');

        /** var FOS\ElasticaBundle\Repository */
        $repository = $repositoryManager->getRepository('App:Article');

        /** var array of App\Entity\Article */
        $article = $repository->searchArticle($params['key'] ?? $params['key']);

        return $article;

    }

    /**
     * @param array $params
     * @return array
     */
    public function getDocumentDetail(array $params)
    {

        /** var FOS\ElasticaBundle\Manager\RepositoryManager */
        $repositoryManager = $this->container->get('fos_elastica.manager');

        /** var FOS\ElasticaBundle\Repository */
        $repository = $repositoryManager->getRepository('App:Article');

        /** var array of App\Entity\Article */
        $article = $repository->searchArticle($params['key'] ?? $params['key']);

        return $article;

    }


}