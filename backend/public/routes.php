<?php
use Slim\App;

use App\Controllers\AuthController;
use App\Controllers\TodoController;
use App\Middlewares\JwtMiddleware;
use App\Utils\ResponseHelper;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Routing\RouteCollectorProxy as Group;

return function (App $app) {
    $app->group("/api", function (Group $appGroup) {
        $appGroup->get("[/]", function (Request $request, Response $response) {
            return ResponseHelper::handle($response, ["message" => "Nothing to see here :3"]);
        });

        $appGroup->post("/login", [AuthController::class, "login"]);
        $appGroup->post("/register", [AuthController::class, "register"]);

        $appGroup->group("/todos", function (Group $todoGroup) {
            $todoGroup->get("[/]", [TodoController::class, "getAll"]);
            $todoGroup->post("[/]", [TodoController::class, "create"]);

            $todoGroup->get("/{id}", [TodoController::class, "get"]);
            $todoGroup->put("/{id}", [TodoController::class, "replace"]);
            $todoGroup->patch("/{id}", [TodoController::class, "update"]);
            $todoGroup->delete("/{id}", [TodoController::class, "delete"]);
        })->add(JwtMiddleware::class);
    });
}
?>