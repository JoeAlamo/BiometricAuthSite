<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 13:00
 */

namespace BiometricSite\ServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;


class DatabaseServiceProvider implements ServiceProviderInterface {
    public function register(Application $app) {
        $app['options.database'] = isset($app['options.database']) ?: [
            'driver' => getenv('DB_DRIVER') ?: 'mysql',
            'dbname' => getenv('DB_NAME') ?: 'biometric',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'user' => getenv('DB_USER') ?: 'biometric',
            'password' => getenv('DB_PASSWORD') ?: 'biometric',
        ];

        $app['database'] = $app->share(function($app) {
            $pdo = new \PDO(
                "{$app['options.database']['driver']}:host={$app['options.database']['host']};dbname={$app['options.database']['dbname']};charset=utf8",
                $app['options.database']['user'],
                $app['options.database']['password']
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });
    }

    public function boot(Application $app) {

    }
} 