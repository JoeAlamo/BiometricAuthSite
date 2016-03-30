<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 08/02/2016
 * Time: 17:28
 */

/**
 * SILEX LICENSE NOTICE
 * Copyright (c) 2010, 2016 Fabien Potencier
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
getenv('APP_ENV') === 'dev' ? $app['debug'] = true : $app['debug'] = false;

/*********************************************************************************
 * PROVIDERS
 ********************************************************************************/
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../resource/view',
]);
$app->register(new BiometricSite\Provider\DatabaseServiceProvider());

/*********************************************************************************
 * REPOSITORIES
 ********************************************************************************/
$app['repository.user'] = function () use ($app) {
    return new BiometricSite\Repository\PDOUserRepository($app['database']);
};

$app['repository.bioClient'] = function () use ($app) {
    return new BiometricSite\Repository\PDOBioClientRepository($app['database']);
};

$app['repository.bioSession'] = function () use ($app) {
   return new BiometricSite\Repository\PDOBioSessionRepository($app['database']);
};

$app['repository.bioAuthSession'] = function () use ($app) {
   return new BiometricSite\Repository\PDOBioAuthSessionRepository($app['database']);
};

/*********************************************************************************
 * SERVICES
 ********************************************************************************/
$app['service.loginAuth'] = function () use ($app) {
    return new BiometricSite\Service\LoginAuthService($app['repository.user']);
};

$app['service.bioAuth.V1'] = function () use ($app) {
    return new BiometricSite\Service\BioAuthV1Service(
        $app['repository.bioClient'],
        $app['repository.bioSession'],
        $app['repository.bioAuthSession']
    );
};

/*********************************************************************************
 * CONTROLLERS
 ********************************************************************************/
$app['controller.home'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\HomeController($app['twig']);
});

$app['controller.loginAuth'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\LoginAuthController(
        $app['request_stack']->getCurrentRequest(),
        $app['twig'],
        $app['service.loginAuth']
    );
});

$app['controller.bioAuth.V1'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\BioAuthV1Controller(
        $app['request_stack']->getCurrentRequest(),
        $app['service.bioAuth.V1']
    );
});

/*********************************************************************************
 * MIDDLEWARE
 ********************************************************************************/
$convertJsonRequestBody = function (\Symfony\Component\HttpFoundation\Request $request) {
    if (strpos($request->headers->get('Content-Type'), 'application/json') === 0) {
        $requestBody = json_decode($request->getContent(), true);
        $request->request->replace(is_array($requestBody) ? $requestBody : []);
    }
};

/*********************************************************************************
 * ROUTES
 ********************************************************************************/
$app->get('/', 'controller.home:indexAction');

$app->get('/authentication/login', 'controller.loginAuth:indexAction');
$app->post('/authentication/login', 'controller.loginAuth:loginAction');

$app->post('/authentication/v1/biometric', 'controller.bioAuth.V1:stage1Action')
    ->before($convertJsonRequestBody);

$app->run();