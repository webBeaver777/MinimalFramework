<?php

declare(strict_types=1);

namespace App\Tasks;

use App\JsonStorage;

final class TaskRepository
{
    public function __construct(private JsonStorage $storage)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function allForUser(int $userId): array
    {
        $all = $this->storage->read();
        $mine = array_values(array_filter($all, fn ($t) => (int) ($t['user_id'] ?? 0) === $userId));
        // normalize types
        foreach ($mine as &$t) {
            $t['id'] = (int) ($t['id'] ?? 0);
            $t['user_id'] = (int) ($t['user_id'] ?? 0);
            $t['completed'] = (bool) ($t['completed'] ?? false);
        }

        return $mine;
    }

    /** @return array<string,mixed> */
    public function create(int $userId, string $title): array
    {
        $all = $this->storage->read();
        $id = 1;
        foreach ($all as $t) {
            $id = max($id, (int) ($t['id'] ?? 0) + 1);
        }
        $task = [
            'id' => $id,
            'user_id' => $userId,
            'title' => $title,
            'completed' => false,
            'created_at' => date('c'),
        ];
        $all[] = $task;
        $this->storage->write($all);

        return $task;
    }

    /** @return array<string,mixed>|null */
    public function findForUser(int $userId, int $id): ?array
    {
        foreach ($this->storage->read() as $t) {
            if ((int) ($t['id'] ?? 0) === $id && (int) ($t['user_id'] ?? 0) === $userId) {
                $t['id'] = (int) $t['id'];
                $t['user_id'] = (int) $t['user_id'];
                $t['completed'] = (bool) ($t['completed'] ?? false);

                return $t;
            }
        }

        return null;
    }

    /** @return array<string,mixed>|null */
    public function updateForUser(int $userId, int $id, array $updates): ?array
    {
        $all = $this->storage->read();
        $updated = null;
        foreach ($all as &$t) {
            if ((int) ($t['id'] ?? 0) === $id && (int) ($t['user_id'] ?? 0) === $userId) {
                $t = array_merge($t, $updates);
                $t['updated_at'] = date('c');
                $updated = $t;
                break;
            }
        }
        $this->storage->write($all);

        return $updated ? $this->findForUser($userId, $id) : null;
    }

    public function deleteForUser(int $userId, int $id): bool
    {
        $all = $this->storage->read();
        $before = count($all);
        $all = array_values(array_filter($all, fn ($t) => !(((int) ($t['id'] ?? 0) === $id) && ((int) ($t['user_id'] ?? 0) === $userId))));
        $this->storage->write($all);

        return $before !== count($all);
    }
}
