<?php

declare(strict_types=1);

namespace App\Auth;

use App\JsonStorage;

final class TokenRepository
{
    public function __construct(private JsonStorage $storage)
    {
    }

    public function issue(int $userId): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $tokens = $this->storage->read();
        $tokens[] = [
            'token' => $token,
            'user_id' => $userId,
            'created_at' => date('c'),
        ];
        $this->storage->write($tokens);

        return $token;
    }

    public function resolveUserId(string $token): ?int
    {
        foreach ($this->storage->read() as $row) {
            if (($row['token'] ?? null) === $token) {
                $id = $row['user_id'] ?? null;
                if (is_int($id)) {
                    return $id > 0 ? $id : null;
                }
                if (is_string($id) && ctype_digit($id)) {
                    $int = (int) $id;

                    return $int > 0 ? $int : null;
                }

                return null;
            }
        }

        return null;
    }
}
