<?php


namespace App\Service;

use App\Service\Tools\EmTransactionService;
use App\Service\Tools\EntitySerializerService;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

abstract class AbstractService
{

    /**
     * @var EntityRepository
     */
    private $repository;
    private $serializerService;
    private $entity;
    /**
     * @var EmTransactionService
     */
    private $transactionService;

    /**
     * AbstractService constructor.
     * @param EmTransactionService $transactionService
     * @param EntitySerializerService $serializerService
     * @param EntityRepository $repository
     * @param $entity
     */
    public function __construct(EmTransactionService $transactionService,
                                EntitySerializerService $serializerService,
                                EntityRepository $repository,
                                $entity)
    {
        $this->transactionService = $transactionService;
        $this->serializerService = $serializerService;
        $this->repository = $repository;
        $this->entity = $entity;
    }


    /**
     * @param array $criteria
     * @return object|null
     */
    public function get($criteria = [])
    {
        $data = $this->repository->findOneBy($criteria, $order = []);

        return $data;
    }


    /**
     * @param array $params
     * @param array $context
     * @return mixed
     */
    public function save(array $params, $context = [])
    {
        $serializerParams = $this->serializerService->deserialize($params, $this->entity, $context);

        return $this->repository->save($serializerParams);
    }


    /**
     * @param $object
     * @return mixed
     */
    public function saveObject($object)
    {
        return $this->repository->save($object);
    }


    /**
     * @param $params
     * @param $object
     * @return mixed
     */
    public function update($params, $object)
    {
        $updateObject = $this->serializerService->deserialize($params, $this->entity,
            [ObjectNormalizer::OBJECT_TO_POPULATE => $object]);
        return $this->repository->save($updateObject);
    }

    /**
     * @param $object
     * @return mixed
     */
    public function delete($object)
    {
        return $this->repository->delete($object);
    }

    /**
     * @param array $criteria
     * @param array $order
     * @param null $limit
     * @param null $offset
     * @return array
     */
    public function findBy($criteria = [], $order = [], $limit = null, $offset = null)
    {
        $data = $this->repository->findBy($criteria, $order = [], $limit = null, $offset = null);

        return $data;
    }


}