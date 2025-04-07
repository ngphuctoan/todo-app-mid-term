<?php
namespace App\Middlewares;

use App\Utils\Database;
use App\Utils\ResponseHelper;

use stdClass;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseFactoryInterface;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class JwtMiddleware implements MiddlewareInterface {
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory) {
        $this->responseFactory = $responseFactory;
    }

    public function process(Request $request, Handler $handler): Response {
        $token = self::getToken($request);
        if (empty($token)) {
            return $this->unauthorisedMessage();
        }

        $pdo = Database::connect();
        $checkStmt = $pdo->prepare("
            select 1 from auth_token_blacklist
            where token = :token and expires_at > NOW()
        ");
        $checkStmt->execute(["token" => $token]);
        if ($checkStmt->fetch()) {
            return $this->unauthorisedMessage();
        }

        $userId = self::decodeToken($token)->sub;
        if (empty($userId)) {
            return $this->unauthorisedMessage();
        }

        $request = $request->withAttribute("user_id", $userId);
        return $handler->handle($request);
    }

    public static function getToken(Request $request): ?string {
        return $request->getCookieParams()["auth_token"] ?? null;
    }

    public static function decodeToken(string $token): ?stdClass {
        try {
            return JWT::decode($token, new Key($_ENV["JWT_SECRET_KEY"], "HS256"));
        } catch (ExpiredException | SignatureInvalidException $error) {
            return null;
        }
    }

    private function unauthorisedMessage(string $message = "Unauthorised."): Response {
        $response = $this->responseFactory->createResponse();
        return ResponseHelper::jsonResponse($response, ["error" => "Unauthorised."], 401);
    }
}
?>