<?php

namespace App\Security;


use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginAuthenticator
{

    private $em;
    private $router;

    public function __construct(EntityManager $em, RouterInterface $router)
    {
        $this->em = $em;
        $this->router = $router;
    }


    public function getCredentials(Request $request)
    {
        $isLoginSubmit = $request->getPathInfo() == '/login' && $request->isMethod('POST');

        if (!$isLoginSubmit) {
            // skip authentication
            return;
        }

        $form = $this->formFactory->create(LoginForm::class);
        $form->handleRequest($request);
        $data = $form->getData();

        return $data;
    }


    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['_username'];
        return $this->em->getRepository('AppBundle:User')
            ->findOneBy(['email' => $username]);
    }


    public function checkCredentials($credentials, UserInterface $user)
    {
        $password = $credentials['_password'];
        if ($password == 'iliketurtles') {
            return true;
        }
        return false;
    }


    protected function getLoginUrl()
    {
        return $this->router->generate('security_login');
    }


    protected function getDefaultSuccessRedirectUrl()
    {
        return $this->router->generate('homepage');
    }
}