<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 08/02/2016
 * Time: 17:28
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;


$app->run();