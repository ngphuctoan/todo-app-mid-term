<?php
use Slim\App;

use App\Controllers\AuthController;
use App\Controllers\TodoController;
use App\Middlewares\JwtMiddleware;
use App\Utils\ResponseHelper;
use App\Utils\PushManager;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Routing\RouteCollectorProxy as Group;

return function (App $app) {
    $app->group("/api", function (Group $appGroup) {
        $appGroup->get("[/]", function (Request $request, Response $response) {
            return ResponseHelper::jsonResponse($response, ["message" => "Nothing to see here :3"]);
        });

        $appGroup->post("/login", [AuthController::class, "login"]);
        $appGroup->post("/logout", [AuthController::class, "logout"]);
        $appGroup->post("/register", [AuthController::class, "register"]);

        $appGroup->group("/todos", function (Group $todoGroup) {
            $todoGroup->get("[/]", [TodoController::class, "getAll"]);
            $todoGroup->post("[/]", [TodoController::class, "create"]);

            $todoGroup->get("/{id}", [TodoController::class, "get"]);
            $todoGroup->put("/{id}", [TodoController::class, "replace"]);
            $todoGroup->patch("/{id}", [TodoController::class, "update"]);
            $todoGroup->delete("/{id}", [TodoController::class, "delete"]);
        })->add(new JwtMiddleware($appGroup->getResponseFactory()));

        $appGroup->group("/push", function (Group $pushGroup) {
            $pushGroup->get("/publickey", [PushManager::class, "getPublicKey"]);
            $pushGroup->post("/subscribe", [PushManager::class, "subscribe"]);
        })->add(new JwtMiddleware($appGroup->getResponseFactory()));
    });
}
?>