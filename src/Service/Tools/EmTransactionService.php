<?php

namespace App\Service\Tools;

use App\Exception\ServiceException;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;

class EmTransactionService {

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function entityManager()
    {
        return $this->em;
    }

    public function start()
    {
        $this->em->getConnection()->beginTransaction();
    }

    /**
     * @throws ConnectionException
     */
    public function end()
    {
        try {
            $this->em->getConnection()->commit();
            $this->em->getConnection()->close();
        } catch (ConnectionException $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @throws ServiceException
     */
    public function rollback()
    {
        try {
            $this->em->getConnection()->rollBack();
            $this->em->getConnection()->close();
        } catch (ConnectionException $e) {
            throw new ServiceException($e);
        }

    }

    public function isTransactionActive()
    {
        return $this->em->getConnection()->isTransactionActive();
    }
}

