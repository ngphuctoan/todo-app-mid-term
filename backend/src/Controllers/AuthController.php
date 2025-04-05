<?php
namespace App\Controllers;

use App\Models\User;
use App\Utils\ResponseHelper;

use Firebase\JWT\JWT;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AuthController {
    public function login(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];
        if (empty($data["user"]) || empty($data["pass"])) {
            return ResponseHelper::handle($response, ["error" => "No username/password provided."], 400);
        }

        $user = User::findByName($data["user"]);
        if (!$user || !password_verify($data["pass"], $user["pass"])) {
            return ResponseHelper::handle($response, ["error" => "Invalid username or password."], 401);
        }

        return ResponseHelper::handle($response, ["token" => $this->generateJwt($user["id"])]);
    }

    public function register(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        if (!isset($data["user"]) || !isset($data["pass"])) {
            return ResponseHelper::handle($response, ["error" => "No username/password provided."], 400);
        }
        if (User::findByName($data["user"])) {
            return ResponseHelper::handle($response, ["error" => "Username already exists."], 400);
        }

        return User::create($data["user"], $data["pass"])
            ? ResponseHelper::handle($response, ["message" => "User has been created!"], 201)
            : ResponseHelper::handle($response, ["error" => "Cannot create user."], 500);
    }

    private function generateJwt(string $userId): string {
        $issuedAt = time();
        $expiresAt = $issuedAt + 604800;

        return JWT::encode([
            "sub" => $userId,
            "iat" => $issuedAt,
            "exp" => $expiresAt
        ], $_ENV["JWT_SECRET_KEY"], "HS256");
    }
}
?>