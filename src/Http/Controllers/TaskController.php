<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\AuthService;
use App\Http\Request;
use App\Http\Response;
use App\Tasks\TaskService;
use InvalidArgumentException;

final class TaskController
{
    public function __construct(private TaskService $tasks, private AuthService $auth)
    {
    }

    public function index(Request $req): void
    {
        $user = $this->requireUser($req);
        $page = max(1, $req->queryInt('page', 1));
        $perPage = max(1, min(100, $req->queryInt('per_page', 10)));
        $data = $this->tasks->listForUserPaginated((int) $user['id'], $page, $perPage);
        Response::json($data, 200);
    }

    public function create(Request $req): void
    {
        $user = $this->requireUser($req);
        $in = $req->json();
        try {
            $task = $this->tasks->create((int) $user['id'], (string) ($in['title'] ?? ''));
            Response::json($task, 201);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(Request $req, int $id): void
    {
        $user = $this->requireUser($req);
        $in = $req->json();
        try {
            $updated = $this->tasks->update((int) $user['id'], $id, $in);
            if (!$updated) {
                Response::json(['error' => 'Task not found'], 404);
            }
            Response::json($updated, 200);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(Request $req, int $id): void
    {
        $user = $this->requireUser($req);
        $ok = $this->tasks->delete((int) $user['id'], $id);
        if (!$ok) {
            Response::json(['error' => 'Task not found'], 404);
        }
        Response::json(['deleted' => $id], 200);
    }

    /** @return array{id:int,email:string} */
    private function requireUser(Request $req): array
    {
        $user = $this->auth->userFromAuthorizationHeader($req->header('authorization'));
        if (!$user) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        return ['id' => (int) $user['id'], 'email' => (string) $user['email']];
    }
}
