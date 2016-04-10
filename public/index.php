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
 * ERROR AND EXCEPTION HANDLING
 ********************************************************************************/
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;

ErrorHandler::register();
ExceptionHandler::register(false);

$app->error(function (\Exception $e, $code) use ($app) {
    error_log(sprintf("\nERROR %s : %s", $code, $e->getMessage()));

    if (strpos($app['request_stack']->getCurrentRequest()->headers->get('Content-Type'), 'application/json') === 0) {
        return new Response('', $code);
    } else {
        return new Response('Sorry, something went wrong.', $code);
    }
});

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

$app['repository.prevClientRandom'] = function () use ($app) {
    return new BiometricSite\Repository\PDOPrevClientRandomRepository($app['database']);
};

$app['repository.prevClientTimestamp'] = function () use ($app) {
    return new BiometricSite\Repository\PDOPrevClientTimestampRepository($app['database']);
};

$app['repository.failedVerificationAttempt'] = function () use ($app) {
    return new \BiometricSite\Repository\PDOFailedVerificationAttemptRepository($app['database']);
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

$app['service.bioAuth.V2'] = function () use ($app) {
    return new \BiometricSite\Service\BioAuthV2Service(
        $app['repository.bioClient'],
        $app['repository.bioSession'],
        $app['repository.bioAuthSession'],
        $app['repository.prevClientRandom']
    );
};

$app['service.bioAuth.V3'] = function () use ($app) {
    return new \BiometricSite\Service\BioAuthV3Service(
        $app['repository.bioClient'],
        $app['repository.bioSession'],
        $app['repository.bioAuthSession'],
        $app['repository.prevClientRandom'],
        $app['repository.prevClientTimestamp']
    );
};

$app['service.failedVerification'] = function () use ($app) {
    return new \BiometricSite\Service\FailedVerificationService(
        $app['repository.bioClient'],
        $app['repository.failedVerificationAttempt']
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

$app['controller.bioAuth.V2'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\BioAuthV2Controller(
        $app['request_stack']->getCurrentRequest(),
        $app['service.bioAuth.V2']
    );
});

$app['controller.bioAuth.V3'] = $app->share(function () use ($app) {
    return new BiometricSite\Controller\BioAuthV3Controller(
        $app['request_stack']->getCurrentRequest(),
        $app['service.bioAuth.V3']
    );
});

$app['controller.failedVerification'] = $app->share(function () use ($app) {
   return new \BiometricSite\Controller\FailedVerificationController(
       $app['request_stack']->getCurrentRequest(),
       $app['service.failedVerification']
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

$app->post('/authentication/v2/biometric', 'controller.bioAuth.V2:stage1Action')
    ->before($convertJsonRequestBody);

$app->post('/authentication/v2/biometric/{session_id}', 'controller.bioAuth.V2:stage2Action')
    ->before($convertJsonRequestBody);

$app->post('/authentication/v3/biometric', 'controller.bioAuth.V3:stage1Action')
    ->before($convertJsonRequestBody);

$app->post('/authentication/v3/biometric/{session_id}', 'controller.bioAuth.V3:stage2Action')
    ->before($convertJsonRequestBody);

$app->post('/authentication/failed-verification', 'controller.failedVerification:logFailedVerificationAction')
    ->before($convertJsonRequestBody);

$app->run();