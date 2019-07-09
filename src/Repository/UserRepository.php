<?php

namespace App\Repository;

use App\Entity\User;
use App\Exception\RepositoryException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param User $user
     * @param bool $autoFlush
     * @return User|null
     * @throws RepositoryException
     */
    public function save(User $user, bool $autoFlush = true): ?User
    {
        try {
            $this->_em->persist($user);

            if ($autoFlush) {
                $this->_em->flush();
            }

            return $user;

        } catch (\Exception $e) {
            throw new RepositoryException($e);
        }
    }
}
