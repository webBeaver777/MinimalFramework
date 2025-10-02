<?php

declare(strict_types=1);

namespace App\Auth;

use App\JsonStorage;

final class AuthService
{
    private UserRepository $users;
    private TokenRepository $tokens;

    public function __construct(string $storageDir)
    {
        $this->users = new UserRepository(new JsonStorage(rtrim($storageDir, '/') . '/users.json'));
        $this->tokens = new TokenRepository(new JsonStorage(rtrim($storageDir, '/') . '/tokens.json'));
    }

    public function register(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Valid email is required');
        }
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }
        if ($this->users->findByEmail($email)) {
            throw new \RuntimeException('Email already registered');
        }
        $user = $this->users->create($email, password_hash($password, PASSWORD_DEFAULT));
        $token = $this->tokens->issue((int) $user['id']);

        return ['token' => $token, 'user' => ['id' => (int) $user['id'], 'email' => $user['email']]];
    }

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            throw new \RuntimeException('Invalid credentials');
        }
        $token = $this->tokens->issue((int) $user['id']);

        return ['token' => $token, 'user' => ['id' => (int) $user['id'], 'email' => $user['email']]];
    }

    public function userFromAuthorizationHeader(?string $header): ?array
    {
        if (!$header || !preg_match('/^Bearer\s+([A-Za-z0-9_\-\.]+)$/', $header, $m)) {
            return null;
        }
        $token = $m[1];
        $userId = $this->tokens->resolveUserId($token);
        if (!$userId) {
            return null;
        }

        return $this->users->findById($userId);
    }
}
