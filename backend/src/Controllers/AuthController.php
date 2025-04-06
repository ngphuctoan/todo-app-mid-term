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
            return ResponseHelper::jsonResponse($response, ["error" => "Username and password required."], 400);
        }

        $user = User::findByName($data["user"]);
        if (!$user || !password_verify($data["pass"], $user["pass"])) {
            return ResponseHelper::jsonResponse($response, ["error" => "Invalid credentials."], 401);
        }

        $authToken = $this->generateJwt($user["id"]);

        /*
            "Secure" flag is turned off for DEMO PURPOSES ONLY!
            For production build, PLEASE TURN THIS FLAG ON!!!

            I am SERIOUS, this is SUPER CRITICAL for security ><

            Note that cookies will ONLY send over HTTPS with this flag on,
            so a certificate is a MUST! (Let's Encrypt certificate is free :3)
        */

        setcookie(
            "auth_token",     // Cookie name
            $authToken,       // JWT auth token
            time() + 604800,  // Expiration time (1 week)
            "/",              // Path
            "",               // Domain (blank is current)
            false,            // "Secure" flag (send over HTTPS only, MUST BE "TRUE" FOR PRODUCTION!)
            true              // "HttpOnly" flag (prevents accessing with JS)
        );

        return ResponseHelper::jsonResponse($response, ["message" => "Logged in successfully!"]);
    }

    public function register(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];
        if (empty($data["user"]) || empty($data["pass"])) {
            return ResponseHelper::jsonResponse($response, ["error" => "Username and password required."], 400);
        }
        if (User::findByName($data["user"])) {
            return ResponseHelper::jsonResponse($response, ["error" => "Username already exists."], 409);
        }

        return User::create($data["user"], $data["pass"])
            ? ResponseHelper::jsonResponse($response, ["message" => "User registered successfully!"], 201)
            : ResponseHelper::jsonResponse($response, ["error" => "An error occurred while creating the user."], 500);
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