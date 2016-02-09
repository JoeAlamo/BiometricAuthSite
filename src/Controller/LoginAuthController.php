<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:11
 */

namespace BiometricSite\Controller;

use Symfony\Component\HttpFoundation\Request;

class LoginAuthController {
    protected $request;
    protected $twig;

    public function __construct(Request $request, \Twig_Environment $twig) {
        $this->request = $request;
        $this->twig = $twig;
    }

    public function indexAction() {
        var_dump($this->request->getClientIp());
        return $this->twig->render('login.twig');
    }

    public function loginAction() {

    }
} 