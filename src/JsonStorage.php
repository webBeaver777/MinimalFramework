<?php

declare(strict_types=1);

namespace App;

class JsonStorage
{
    public function __construct(private readonly string $filePath)
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!file_exists($this->filePath)) {
            $this->write([]);
        }
    }

    /**
     * @return array<mixed>
     */
    public function read(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $fp = fopen($this->filePath, 'rb');
        if ($fp === false) {
            return [];
        }
        try {
            flock($fp, LOCK_SH);
            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }
        if ($content === false || $content === '') {
            return [];
        }
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<mixed> $data
     */
    public function write(array $data): void
    {
        $tmp = $this->filePath . '.tmp';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($tmp, $json !== false ? $json : '[]', LOCK_EX);
        rename($tmp, $this->filePath);
    }
}
