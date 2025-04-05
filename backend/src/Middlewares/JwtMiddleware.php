<?php
namespace App\Middlewares;

use App\Utils\Database;
use App\Utils\ResponseHelper;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class JwtMiddleware implements MiddlewareInterface {
    public function process(Request $request, Handler $handler): Response {
        $token = $this->getToken($request);
        if (!$token) {
            $response = $handler->handle($request);
            return ResponseHelper::handle($response, ["error" => "No token provided."], 401);
        }

        $userId = $this->decodeToken($token);
        if (!$userId) {
            $response = $handler->handle($request);
            return ResponseHelper::handle($response, ["error" => "Invalid or expired token."], 401);
        }

        $request = $request->withAttribute("user_id", $userId);
        return $handler->handle($request);
    }

    private function getToken(Request $request): ?string {
        $authHeader = $request->getHeaderLine("Authorization");
        return str_replace("Bearer ", "", $authHeader);
    }

    private function decodeToken(string $token): ?int {
        try {
            $decoded = JWT::decode($token, new Key($_ENV["JWT_SECRET_KEY"], "HS256"));
            return $decoded->sub ?? null;
        } catch (ExpiredException | SignatureInvalidException $error) {
            return null;
        }
    }
}
?>