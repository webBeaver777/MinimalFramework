<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public readonly string $method;
    public readonly string $path;
    /** @var array<string,string> */
    public readonly array $headers;
    /** @var array<string,mixed> */
    private array $json;
    /** @var array<string,string> */
    private array $query;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $rawPath = parse_url($uri, PHP_URL_PATH) ?: '/';
        $trimmed = rtrim($rawPath, '/');
        $this->path = $trimmed === '' ? '/' : $trimmed;
        $this->headers = $this->readHeaders();
        $this->json = $this->readJsonBody();
        $this->query = $this->readQuery();
    }

    /** @return array<string,mixed> */
    public function json(): array
    {
        return $this->json;
    }

    public function header(string $name): ?string
    {
        $needle = strtolower($name);

        return $this->headers[$needle] ?? null;
    }

    public function query(string $key, ?string $default = null): ?string
    {
        return $this->query[$key] ?? $default;
    }

    public function queryInt(string $key, int $default): int
    {
        $val = $this->query($key);
        if ($val === null) {
            return $default;
        }
        if (!preg_match('/^-?\d+$/', $val)) {
            return $default;
        }

        return (int) $val;
    }

    /** @return array<string,string> */
    private function readHeaders(): array
    {
        $out = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $out[$name] = (string) $v;
            }
        }
        // Некоторые заголовки не имеют префикса HTTP_
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $out['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $out['content-length'] = (string) $_SERVER['CONTENT_LENGTH'];
        }
        // Fallback через getallheaders, если доступно
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $key = strtolower(str_replace('_', '-', (string) $name));
                if (!isset($out[$key])) {
                    $out[$key] = (string) $value;
                }
            }
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /** @return array<string,string> */
    private function readQuery(): array
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        parse_str($qs, $out);
        $result = [];
        foreach ($out as $k => $v) {
            if (is_scalar($v)) {
                $result[(string) $k] = (string) $v;
            }
        }

        return $result;
    }
}
