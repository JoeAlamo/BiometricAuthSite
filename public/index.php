<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 08/02/2016
 * Time: 17:28
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
getenv('APP_ENV') === 'dev' ? $app['debug'] = true : $app['debug'] = false;

/*********************************************************************************
 * PROVIDERS
 ********************************************************************************/
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../resource/view',
]);
$app->register(new BiometricSite\ServiceProvider\DatabaseServiceProvider());

/*********************************************************************************
 * CONTROLLERS
 ********************************************************************************/
$app['home.controller'] = $app->share(function () use ($app) {
   return new BiometricSite\Controller\HomeController($app['twig']);
});
$app['login.controller'] = $app->share(function () use ($app) {
   return new BiometricSite\Controller\LoginAuthController($app['request_stack']->getCurrentRequest(), $app['twig']);
});

/*********************************************************************************
 * ROUTES
 ********************************************************************************/
$app->get('/', 'home.controller:indexAction');

$app->get('/authentication/login', 'login.controller:indexAction');
$app->post('/authentication/login', 'login.controller:loginAction');

$app->run();