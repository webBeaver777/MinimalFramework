<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\AuthService;
use App\Http\Request;
use App\Http\Response;
use InvalidArgumentException;
use RuntimeException;

final class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function register(Request $req): void
    {
        $in = $req->json();
        try {
            $res = $this->auth->register((string) ($in['email'] ?? ''), (string) ($in['password'] ?? ''));
            Response::json($res, 201);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $code = $e->getMessage() === 'Email already registered' ? 409 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    public function login(Request $req): void
    {
        $in = $req->json();
        try {
            $res = $this->auth->login((string) ($in['email'] ?? ''), (string) ($in['password'] ?? ''));
            Response::json($res, 200);
        } catch (RuntimeException $e) {
            $code = $e->getMessage() === 'Invalid credentials' ? 401 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    public function me(Request $req): void
    {
        $user = $this->auth->userFromAuthorizationHeader($req->header('authorization'));
        if (!$user) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        Response::json(['id' => (int) $user['id'], 'email' => (string) $user['email']], 200);
    }
}
