<?php
namespace App\Utils;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseHelper {
    public static function handle(Response $response, array $body, int $status = 200) {
        $response->getBody()->write(json_encode($body));
        return $response->withHeader("Content-Type", "application/json")->withStatus($status);
    }
}
?>