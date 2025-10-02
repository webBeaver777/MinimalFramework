<?php

declare(strict_types=1);

namespace App\Tasks;

final class TaskService
{
    public function __construct(private TaskRepository $repo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function listForUser(int $userId): array
    {
        return $this->repo->allForUser($userId);
    }

    /**
     * @return array{items:array<int,array<string,mixed>>,page:int,per_page:int,total:int,total_pages:int}
     */
    public function listForUserPaginated(int $userId, int $page, int $perPage): array
    {
        $perPage = max(1, min(100, $perPage));
        $all = $this->repo->allForUser($userId);
        $total = count($all);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($all, $offset, $perPage);

        return [
            'items' => $items,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    /** @return array<string,mixed> */
    public function create(int $userId, string $title): array
    {
        $title = trim($title);
        $this->assertTitle($title);

        return $this->repo->create($userId, $title);
    }

    /** @return array<string,mixed>|null */
    public function update(int $userId, int $id, array $payload): ?array
    {
        $updates = [];
        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            $this->assertTitleForUpdate($title);
            $updates['title'] = $title;
        }
        if (array_key_exists('completed', $payload)) {
            $updates['completed'] = $this->toBool($payload['completed']);
        }
        if ($updates === []) {
            throw new \InvalidArgumentException('Nothing to update');
        }

        return $this->repo->updateForUser($userId, $id, $updates);
    }

    public function delete(int $userId, int $id): bool
    {
        return $this->repo->deleteForUser($userId, $id);
    }

    private function assertTitle(string $title): void
    {
        if ($title === '') {
            throw new \InvalidArgumentException('title is required');
        }
        if (strlen($title) > 255) {
            throw new \InvalidArgumentException('title is too long (max 255)');
        }
    }

    private function assertTitleForUpdate(string $title): void
    {
        if ($title === '') {
            throw new \InvalidArgumentException('title cannot be empty');
        }
        if (strlen($title) > 255) {
            throw new \InvalidArgumentException('title is too long (max 255)');
        }
    }

    /** @param mixed $v */
    private function toBool($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v)) {
            return $v !== 0;
        }
        if (is_string($v)) {
            $lv = strtolower(trim($v));
            if (in_array($lv, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lv, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }

            return (bool) $v;
        }

        return (bool) $v;
    }
}
