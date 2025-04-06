<?php
namespace App\Controllers;

use App\Models\Todo;
use App\Utils\ResponseHelper;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class TodoController {
    public function get(Request $request, Response $response, array $args): Response {
        $userId = $request->getAttribute("user_id");
        if (!$userId)
            return $response;

        $todoId = (int) $args["id"];
        if (!$todoId)
            return ResponseHelper::handle($response, ["error" => "Todo ID not provided!"], 400);
        
        $todo = Todo::findById($userId, $todoId);
        return $todo
            ? ResponseHelper::handle($response, $todo)
            : ResponseHelper::handle($response, ["error" => "Todo not found!"], 404);
    }

    public function getAll(Request $request, Response $response): Response {
        $userId = $request->getAttribute("user_id");
        if (!$userId)
            return $response;

        $todos = Todo::findAll($userId);
        return ResponseHelper::handle($response, $todos);
    }

    public function create(Request $request, Response $response): Response {
        $userId = $request->getAttribute("user_id");
        if (!$userId)
            return $response;

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (empty($data["title"]))
            return ResponseHelper::handle($response, ["error" => "Todo title not provided!"], 400);

        $todoId = Todo::create($userId, [
            "title" => $data["title"],
            "description" => $data["description"] ?? null,
            "reminder" => $data["reminder"] ?? null
        ]);

        return $todoId
            ? $this->get($request, $response, ["id" => $todoId])
            : ResponseHelper::handle($response, ["error" => "Cannot create todo!"], 500);
    }

    public function replace(Request $request, Response $response, array $args): Response {
        $userId = $request->getAttribute("user_id");
        if (!$userId)
            return $response;

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $todoId = (int) $args["id"];
        if (!$todoId)
            return ResponseHelper::handle($response, ["error" => "Todo ID not provided!"], 400);

        if (empty($data["title"]))
            return ResponseHelper::handle($response, ["error" => "Todo title not provided!"], 400);

        return Todo::update($userId, $todoId, [
            "title" => $data["title"],
            "description" => $data["description"] ?? null,
            "is_completed" => $data["is_completed"] ?? false,
            "reminder" => $data["reminder"] ?? null
        ])
            ? $this->get($request, $response, ["id" => $todoId])
            : ResponseHelper::handle($response, ["error" => "Cannot replace todo!"], 500);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $userId = $request->getAttribute("user_id");
        if (!$userId)
            return $response;

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $todoId = (int) $args["id"];
        if (!$todoId)
            return ResponseHelper::handle($response, ["error" => "Todo ID not provided!"], 400);

        $oldTodo = Todo::findById($userId, $todoId);
        if (!$oldTodo)
            return ResponseHelper::handle($response, ["error" => "Todo not found!"], 404);

        return Todo::update($userId, $todoId, [
            "title" => $data["title"] ?? $oldTodo["title"],
            "description" => $data["description"] ?? $oldTodo["description"],
            "is_completed" => $data["is_completed"] ?? $oldTodo["is_completed"],
            "reminder" => $data["reminder"] ?? $oldTodo["reminder"]
        ])
            ? $this->get($request, $response, ["id" => $todoId])
            : ResponseHelper::handle($response, ["error" => "Cannot update todo!"], 500);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $userId = $request->getAttribute("user_id");
        if (!$userId)
            return $response;

        $todoId = (int) $args["id"];
        if (!$todoId)
            return ResponseHelper::handle($response, ["error" => "Todo ID not provided!"], 400);
        
        $oldTodo = Todo::findById($userId, $todoId);
        if (!$oldTodo)
            return ResponseHelper::handle($response, ["error" => "Todo not found!"], 404);

        return Todo::delete($userId, $todoId)
            ? ResponseHelper::handle($response, $oldTodo)
            : ResponseHelper::handle($response, ["error" => "Cannot delete todo!"], 500);
    }
}
?>