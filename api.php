<?php

declare(strict_types=1);

use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

require __DIR__ . '/vendor/autoload.php';

use App\Auth\AuthService;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Request;
use App\Http\Response;
use App\JsonStorage;
use App\Tasks\TaskRepository;
use App\Tasks\TaskService;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

// CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

$storageDir = __DIR__ . '/storage';
$authService = new AuthService($storageDir);
$taskService = new TaskService(new TaskRepository(new JsonStorage($storageDir . '/tasks.json')));

$authController = new AuthController($authService);
$taskController = new TaskController($taskService, $authService);

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Auth
    $r->addRoute('POST', '/auth/register', ['AuthController', 'register']);
    $r->addRoute('POST', '/auth/login', ['AuthController', 'login']);
    $r->addRoute('GET', '/me', ['AuthController', 'me']);

    // Tasks
    $r->addRoute('GET', '/tasks', ['TaskController', 'index']);
    $r->addRoute('POST', '/tasks', ['TaskController', 'create']);
    $r->addRoute('PATCH', '/tasks/{id:\\d+}', ['TaskController', 'update']);
    $r->addRoute('DELETE', '/tasks/{id:\\d+}', ['TaskController', 'delete']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$normalized = rtrim($uri, '/');
$uriToDispatch = $normalized === '' ? '/' : $normalized;
$routeInfo = $dispatcher->dispatch($httpMethod, $uriToDispatch);

$request = new Request();

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        Response::json(['error' => 'Not found'], 404);
        // no break
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        Response::json(['error' => 'Method not allowed'], 405);
        // no break
    case FastRoute\Dispatcher::FOUND:
        [$controllerName, $action] = $routeInfo[1];
        $vars = $routeInfo[2] ?? [];
        if ($controllerName === 'AuthController') {
            $controller = $authController;
        } elseif ($controllerName === 'TaskController') {
            $controller = $taskController;
        } else {
            Response::json(['error' => 'Not found'], 404);
        }
        try {
            // Call action
            if ($action === 'update' || $action === 'delete') {
                $id = (int) ($vars['id'] ?? 0);
                $controller->$action($request, $id);
            } else {
                $controller->$action($request);
            }
        } catch (\Throwable $e) {
            // Логируем в error_log и отвечаем стандартным сообщением
            error_log('[API ERROR] ' . $e::class . ': ' . $e->getMessage());
            Response::json(['error' => 'Internal server error'], 500);
        }
}
