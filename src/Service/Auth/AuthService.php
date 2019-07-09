<?php


namespace App\Service\Auth;


use App\Entity\User;
use App\Exception\ServiceException;
use App\Repository\UserRepository;
use App\Resources\Redis\RedisHelper;
use App\Security\TokenAuthenticator;
use App\Service\AbstractService;
use App\Service\Tools\EmTransactionService;
use App\Service\Tools\EntitySerializerService;
use Symfony\Component\HttpFoundation\Request;

class AuthService extends AbstractService
{
    private $ttl = 360;

    private $entity = User::class;
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var \Redis
     */
    private $redis;


    /**
     * AuthService constructor.
     * @param EmTransactionService $transactionService
     * @param EntitySerializerService $serializerService
     * @param UserRepository $repository
     * @param $sncRedisDefault
     */
    public function __construct(EmTransactionService $transactionService,
                                EntitySerializerService $serializerService,
                                UserRepository $repository,
                                $sncRedisDefault
    )
    {
        parent::__construct($transactionService, $serializerService, $repository, $this->entity);
        $this->repository = $repository;
        $this->redis = $sncRedisDefault;
    }


    /**
     * @param array $params
     * @return array
     * @throws ServiceException
     */
    public function loginUser(array $params)
    {
        try {
            $token = false;

            $user = $this->get([
                'userName' => $params['user_name'],
                'password' => sha1($params['password'])
            ]);

            if (!is_null($user)) {

                $token = $this->generateToken();

                $this->redis->set($token, $user->getId());

                $this->redis->expire($token, $this->ttl);

                $this->update(['api_token' => $token], $user);
            }

            return ['X-AUTH-TOKEN' => $token];

        } catch (\Exception $e) {
            throw new ServiceException($e);
        }
    }


    /**
     * @return string
     */
    private function generateToken()
    {
        return (
            dechex(mt_rand(0, 0xFFFF)) .
            dechex(mt_rand(0, 0xFFFF)) .
            dechex(mt_rand(0, 0xFFFF)) .
            dechex(mt_rand(0, 0xFFFF)) .
            dechex(mt_rand(0, 0xFFFF))
        );
    }


}