<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:12
 */

namespace BiometricSite\Controller;


class HomeController {
    protected $twig;

    public function __construct(\Twig_Environment $twig) {
        $this->twig = $twig;
    }

    public function indexAction() {
        return $this->twig->render('index.twig');
    }
} 