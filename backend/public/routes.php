<?php
use Slim\App;

use App\Controllers\AuthController;

use Slim\Routing\RouteCollectorProxy as Group;

return function (App $app) {
    $app->group("/api", function (Group $group) {
        $group->post("/login", [AuthController::class, "login"]);
        $group->post("/register", [AuthController::class, "register"]);
    });
}
?>