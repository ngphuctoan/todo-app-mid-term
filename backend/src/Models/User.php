<?php
namespace App\Models;

use App\Utils\Database;

class User {
    public static function findByName(string $name): ?array {
        $pdo = Database::connect();

        $findStmt = $pdo->prepare("
            select * from users
            where name = :name
            limit 1
        ");
        $findStmt->execute(["name" => $name]);

        return $findStmt->fetch() ?: null;
    }

    public static function create(string $name, string $pass): bool {
        $pdo = Database::connect();
        $hashedPass = password_hash($pass, PASSWORD_BCRYPT);
        
        $registerStmt = $pdo->prepare("
            insert into users (name, pass)
            values (:name, :pass)
        ");
        return $registerStmt->execute([
            "name" => $name,
            "pass" => $hashedPass
        ]);
    }
}
?>