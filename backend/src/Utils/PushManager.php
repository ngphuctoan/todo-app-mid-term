<?php
namespace App\Utils;

use App\Utils\Database;
use App\Utils\ResponseHelper;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PushManager {
    public static function getPublicKey(Request $request, Response $response): Response {
        return ResponseHelper::jsonResponse($response, ["public_key" => $_ENV["VAPID_PUBLIC_KEY"]]);
    }

    public static function subscribe(Request $request, Response $response): Response {
        $pdo = Database::connect();
        $userId = $request->getAttribute("user_id");
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $endpoint = $data["endpoint"] ?? null;
        $publicKey = $data["keys"]["p256dh"] ?? null;
        $pushAuth = $data["keys"]["auth"] ?? null;

        if (!$endpoint || !$publicKey || !$pushAuth) {
            return ResponseHelper::jsonResponse($response, ["error" => "Invalid subscription data."], 400);
        }

        $addStmt = $pdo->prepare("
            replace into push_subscriptions (user_id, endpoint, public_key, push_auth)
            values (:user_id, :endpoint, :public_key, :push_auth)
        ");
        $addStmt->execute([
            "user_id" => $userId,
            "endpoint" => $endpoint,
            "public_key" => $publicKey,
            "push_auth" => $pushAuth
        ]);

        return ResponseHelper::jsonResponse($response, ["message" => "Registered push successfully!"]);
    }
}
?>