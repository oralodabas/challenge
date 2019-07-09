<?php


namespace App\Controller\Auth;

use App\Controller\BaseApiController;
use App\Service\Auth\AuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class AuthController extends BaseApiController
{

    /**
     * @var AuthService
     */
    private $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \App\Exception\ServiceException
     * @Route("/login",name="login",methods={"POST"})
     */
    public function getLoginAction(Request $request)
    {
        $params = $this->getRequestContentParams($request);

        $response = $this->service->loginUser($params);

        $response = $this->successResponse($response);

        return $response;
    }

}