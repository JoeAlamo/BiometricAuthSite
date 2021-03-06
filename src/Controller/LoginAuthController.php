<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:11
 */

namespace BiometricSite\Controller;

use BiometricSite\Service\LoginAuthServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginAuthController implements LoginAuthControllerInterface
{
    private $request;
    private $twig;
    private $loginAuthService;

    public function __construct(Request $request, \Twig_Environment $twig, LoginAuthServiceInterface $loginAuthService) {
        $this->request = $request;
        $this->twig = $twig;
        $this->loginAuthService = $loginAuthService;
    }

    public function indexAction() {
        return $this->twig->render('login.twig');
    }

    public function loginAction() {
        return $this->loginAuthService->authenticateUser(
            $this->request->request->get('username'),
            $this->request->request->get('password'),
            $this
        );
    }

    public function successfulLogin($previousSessions) {
        return $this->twig->render('loginSuccess.twig', $previousSessions);
    }

    public function unsuccessfulLogin() {
        return new Response('Login unsuccessful', Response::HTTP_FORBIDDEN);
    }
}