<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 01/04/2016
 * Time: 18:17
 */
namespace BiometricSite\Controller;

interface LoginAuthControllerInterface {
    public function successfulLogin($previousSessions);

    public function unsuccessfulLogin();
}