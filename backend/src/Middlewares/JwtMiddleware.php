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
use Psr\Http\Message\ResponseFactoryInterface;

class JwtMiddleware implements MiddlewareInterface {
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory) {
        $this->responseFactory = $responseFactory;
    }

    public function process(Request $request, Handler $handler): Response {
        $token = $this->getToken($request);
        if (!$token) {
            return $this->redirectToLogin();
        }

        $userId = $this->decodeToken($token);
        if (!$userId) {
            return $this->redirectToLogin();
        }

        $request = $request->withAttribute("user_id", $userId);
        return $handler->handle($request);
    }

    private function getToken(Request $request): ?string {
        return $request->getCookieParams()["auth_token"] ?? null;
    }

    private function decodeToken(string $token): ?int {
        try {
            $decoded = JWT::decode($token, new Key($_ENV["JWT_SECRET_KEY"], "HS256"));
            return $decoded->sub ?? null;
        } catch (ExpiredException | SignatureInvalidException $error) {
            return null;
        }
    }

    private function redirectToLogin(): Response {
        $response = $this->responseFactory->createResponse(302);
        return $response->withHeader("Location", "/login");
    }
}
?>