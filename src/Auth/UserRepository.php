<?php

declare(strict_types=1);

namespace App\Auth;

use App\JsonStorage;

final class UserRepository
{
    public function __construct(private JsonStorage $storage)
    {
    }

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        foreach ($this->storage->read() as $u) {
            if (strtolower((string) ($u['email'] ?? '')) === $email) {
                return $u;
            }
        }

        return null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        foreach ($this->storage->read() as $u) {
            if ((int) ($u['id'] ?? 0) === $id) {
                return $u;
            }
        }

        return null;
    }

    /** @return array<string,mixed> */
    public function create(string $email, string $passwordHash): array
    {
        $users = $this->storage->read();
        $nextId = 1;
        foreach ($users as $u) {
            $nextId = max($nextId, (int) ($u['id'] ?? 0) + 1);
        }
        $user = [
            'id' => $nextId,
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'created_at' => date('c'),
        ];
        $users[] = $user;
        $this->storage->write($users);

        return $user;
    }
}
