<?php

require_once getcwd() . '/../vendor/autoload.php';

use App\Modules\Configuration\Router;
use App\Controllers\AppController;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

if(env('APP_TIMEZONE')) {
    date_default_timezone_set(env('APP_TIMEZONE'));
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

try {
    dump($method, $path);
    $router = new Router($method, $path);

    $router->add('GET', '/', function ($params) {
        return AppController::home();
    });

    $router->add('POST', '/process', function ($params) {
        return AppController::process();
    });

    $router->add('POST', '/download', function ($params) {
        return AppController::download();
    });

    $result = $router->handler();

    if (!$result) {
        http_response_code(404);
        echo "Erro 404";
        die();
    }

    echo $result($router->get('params'));
} catch (Exception $e) {
    dd($e);
}

