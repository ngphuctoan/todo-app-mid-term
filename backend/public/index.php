<?php
require __DIR__ . "/../../vendor/autoload.php";

use App\Controllers\TodoController;

use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;

$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

$container->set(ResponseFactoryInterface::class, ResponseFactory::class);
$container->set(TodoController::class, function ($container) {
    return new TodoController($container->get(ResponseFactoryInterface::class));
});

$app = Bridge::create($container);

(require __DIR__ . "/routes.php")($app);

$app->run();
?>