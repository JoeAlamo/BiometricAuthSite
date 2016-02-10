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
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../resource/view',
]);
$app->register(new BiometricSite\ServiceProvider\DatabaseServiceProvider());

/*********************************************************************************
 * REPOSITORIES
 ********************************************************************************/
$app['repository.user'] = $app->share(function () use ($app) {
    return new BiometricSite\Repository\PDOUserRepository($app['database']);
});

$app['repository.bioClient'] = $app->share(function () use ($app) {
    return new BiometricSite\Repository\PDOBioClientRepository($app['database']);
});

$app['repository.bioSession'] = $app->share(function () use ($app) {
   return new BiometricSite\Repository\PDOBioSessionRepository($app['database']);
});

$app['repository.bioAuthSession'] = $app->share(function () use ($app) {
   return new BiometricSite\Repository\PDOBioAuthSessionRepository($app['database']);
});

/*********************************************************************************
 * SERVICES
 ********************************************************************************/
$app['service.loginAuth'] = $app->share(function () use ($app) {
    return new BiometricSite\Service\LoginAuthService($app['repository.user']);
});

$app['service.bioAuth.V1'] = $app->share(function () use ($app) {
    return new BiometricSite\Service\BioAuth\V1\BioAuthService($app['repository.bioClient'], $app['repository.bioSession'], $app['repository.bioAuthSession']);
});

/*********************************************************************************
 * CONTROLLERS
 ********************************************************************************/
$app['controller.home'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\HomeController($app['twig']);
});

$app['controller.loginAuth'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\LoginAuthController($app['request_stack']->getCurrentRequest(), $app['twig'], $app['service.loginAuth']);
});

$app['controller.bioAuth.V1'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\BioAuthController($app['request_stack']->getCurrentRequest(), $app['service.bioAuth.V1']);
});

/*********************************************************************************
 * MIDDLEWARE
 ********************************************************************************/
$jsonRequestTransform = function (\Symfony\Component\HttpFoundation\Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
};

/*********************************************************************************
 * ROUTES
 ********************************************************************************/
$app->get('/', 'controller.home:indexAction');

$app->get('/authentication/login', 'controller.loginAuth:indexAction');
$app->post('/authentication/login', 'controller.loginAuth:loginAction');

$app->post('/authentication/biometric/v1', 'controller.bioAuth.V1:authenticateV1Action')
    ->before($jsonRequestTransform);

$app->run();