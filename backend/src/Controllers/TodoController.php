<?php
namespace App\Controllers;

use App\Models\Todo;
use App\Utils\ResponseHelper;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class TodoController {
    public function get(Request $request, Response $response, array $args): Response {
        $todoId = (int) $args["id"];
        $userId = $request->getAttribute("user_id");
        
        $todo = Todo::findById($userId, $todoId);
        return $todo
            ? ResponseHelper::jsonResponse($response, $todo)
            : ResponseHelper::jsonResponse($response, ["error" => "Cannot find todo."], 404);
    }

    public function getAll(Request $request, Response $response): Response {
        $userId = $request->getAttribute("user_id");

        $todos = Todo::findAll($userId);
        return ResponseHelper::jsonResponse($response, $todos);
    }

    public function create(Request $request, Response $response): Response {
        $userId = $request->getAttribute("user_id");

        $data = json_decode($request->getBody()->getContents(), true) ?? [];
        if (empty($data["title"]))
            return ResponseHelper::jsonResponse($response, ["error" => "Todo title is required."], 400);

        $todoId = Todo::create($userId, [
            "title" => $data["title"],
            "description" => $data["description"] ?? null,
            "reminder" => $data["reminder"] ?? null
        ]);

        return $todoId
            ? $this->get($request, $response, ["id" => $todoId])
            : ResponseHelper::jsonResponse($response, ["error" => "An error occurred while creating the todo."], 500);
    }

    public function replace(Request $request, Response $response, array $args): Response {
        $todoId = (int) $args["id"];
        $userId = $request->getAttribute("user_id");

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (empty($data["title"]))
            return ResponseHelper::jsonResponse($response, ["error" => "Todo title not provided!"], 400);

        return Todo::update($userId, $todoId, [
            "title" => $data["title"],
            "description" => $data["description"] ?? null,
            "is_completed" => $data["is_completed"] ?? false,
            "reminder" => $data["reminder"] ?? null
        ])
            ? $this->get($request, $response, ["id" => $todoId])
            : ResponseHelper::jsonResponse($response, ["error" => "An error occurred while replacing the todo."], 500);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $todoId = (int) $args["id"];
        $userId = $request->getAttribute("user_id");

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $oldTodo = Todo::findById($userId, $todoId);
        if (!$oldTodo)
            return ResponseHelper::jsonResponse($response, ["error" => "Cannot find todo."], 404);

        return Todo::update($userId, $todoId, [
            "title" => $data["title"] ?? $oldTodo["title"],
            "description" => $data["description"] ?? $oldTodo["description"],
            "is_completed" => $data["is_completed"] ?? $oldTodo["is_completed"],
            "reminder" => $data["reminder"] ?? $oldTodo["reminder"]
        ])
            ? $this->get($request, $response, ["id" => $todoId])
            : ResponseHelper::jsonResponse($response, ["error" => "An error occurred while updating the todo."], 500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $todoId = (int) $args["id"];
        $userId = $request->getAttribute("user_id");
        
        $oldTodo = Todo::findById($userId, $todoId);
        if (!$oldTodo)
            return ResponseHelper::jsonResponse($response, ["error" => "Cannot find todo."], 404);

        return Todo::delete($userId, $todoId)
            ? ResponseHelper::jsonResponse($response, $oldTodo)
            : ResponseHelper::jsonResponse($response, ["error" => "An error occurred while deleting the todo."], 500);
    }
}
?>