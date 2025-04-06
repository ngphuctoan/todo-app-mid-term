<?php
namespace App\Models;

use App\Utils\Database;
use InvalidArgumentException;

class Todo {
    public static function findById(int $userId, int $todoId): ?array {
        $pdo = Database::connect();

        $getStmt = $pdo->prepare("
            select id, title, description, is_completed, reminder from todos
            where id = :todo_id and user_id = :user_id
        ");
        $getStmt->execute([
            "todo_id" => $todoId,
            "user_id" => $userId
        ]);

        return $getStmt->fetch() ?: null;
    }

    public static function findAll(int $userId): array {
        $pdo = Database::connect();

        $getStmt = $pdo->prepare("
            select id, title, description, is_completed, reminder from todos
            where user_id = :user_id
            order by case when reminder is null then 1 else 0 end, reminder
        ");
        $getStmt->execute(["user_id" => $userId]);

        return $getStmt->fetchAll() ?: [];
    }

    public static function create(int $userId, array $data): ?int {
        $pdo = Database::connect();

        $createStmt = $pdo->prepare("
            insert into todos (user_id, title, description, is_completed, reminder)
            values (:user_id, :title, :description, :is_completed, :reminder)
        ");
        return $createStmt->execute([
            "user_id" => $userId,
            "title" => $data["title"],
            "description" => $data["description"] ?? null,
            "is_completed" => false,
            "reminder" => $data["reminder"] ?? null
        ]) ? $pdo->lastInsertId() : null;
    }

    public static function update(int $userId, int $todoId, array $data): bool {
        $pdo = Database::connect();

        $createStmt = $pdo->prepare("
            update todos
            set title = :title, description = :description, is_completed = :is_completed, reminder = :reminder
            where id = :todo_id and user_id = :user_id
        ");
        return $createStmt->execute([
            "user_id" => $userId,
            "todo_id" => $todoId,
            "title" => $data["title"] ?? null,
            "description" => $data["description"] ?? null,
            "is_completed" => $data["is_completed"],
            "reminder" => $data["reminder"] ?? null
        ]);
    }

    public static function delete(int $userId, int $todoId): bool {
        $pdo = Database::connect();

        $deleteStmt = $pdo->prepare("
            delete from todos
            where id = :todo_id and user_id = :user_id
        ");
        return $deleteStmt->execute([
            "todo_id" => $todoId,
            "user_id" => $userId
        ]);
    }
}
?>