<?php
namespace App\Middlewares;

use App\Utils\Database;
use App\Utils\ResponseHelper;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class JwtMiddleware {
    public function __invoke(Request $request, Response $response, callable $next): Response {
        $token = $this->getToken($request);
        if (!$token) {
            return ResponseHelper::handle($response, ["error" => "No token provided."], 401);
        }

        $userId = $this->decodeToken($token);
        if (!$userId) {
            return ResponseHelper::handle($response, ["error" => "Invalid or expired token."], 401);
        }

        return $next($request->withAttribute("user_id", $userId), $response);
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