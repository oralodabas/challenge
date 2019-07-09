<?php

namespace App\Resources\ElasticUtil;


use App\Exception\ElasticException;
use FOS\ElasticaBundle\Elastica\Index;
use FOS\ElasticaBundle\Index\IndexManager;
use InvalidArgumentException;

class ElasticUtil
{

    private $manager;

    public function __construct(IndexManager $manager)
    {
        $this->manager = $manager;
    }

    public function get(){
        return $this->manager;
    }

    /**
     * @param string $name
     * @return Index
     * @throws ElasticException
     */
    public function getOne(string $name): Index
    {
        return $this->find($name);
    }

    public function getAll(): iterable
    {
        return $this->manager->getAllIndexes();
    }

    /**
     * @param string $name
     * @return string
     * @throws ElasticException
     */
    public function getAliasName(string $name): string
    {
        return $this->find($name)->getName();
    }

    /**
     * @param string $name
     * @return Index
     * @throws ElasticException
     */
    private function find(string $name): Index
    {
        try {
            return $this->manager->getIndex($name);
        } catch (InvalidArgumentException $e) {
            throw new ElasticException('Index not found.');
        }
    }
}