<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * Tables stored as JSON files, used on hosting without database access.
 * Every write locks its file, so parallel requests cannot overwrite each other.
 */
final class JsonStore
{
    public function __construct(private readonly string $dir)
    {
    }

    /**
     * @return array<int, array> rows keyed by id, in insertion order
     */
    public function all(string $table): array
    {
        $file = $this->file($table);

        if (!is_file($file)) {
            return [];
        }

        $handle = fopen($file, 'r');
        flock($handle, LOCK_SH);
        $data = $this->decode(stream_get_contents($handle));
        flock($handle, LOCK_UN);
        fclose($handle);

        return $data['rows'];
    }

    public function find(string $table, int $id): ?array
    {
        return $this->all($table)[$id] ?? null;
    }

    /**
     * Uses $row['id'] when given, otherwise the next free id. Ids are never reused.
     */
    public function insert(string $table, array $row): int
    {
        $id = 0;

        $this->mutate($table, function (array $data) use ($row, &$id) {
            $id = (int) ($row['id'] ?? $data['next_id']);
            $data['rows'][$id] = array_merge(['id' => $id], $row, ['id' => $id]);
            $data['next_id'] = max($data['next_id'], $id + 1);

            return $data;
        });

        return $id;
    }

    public function update(string $table, int $id, array $changes): void
    {
        $this->mutate($table, function (array $data) use ($id, $changes) {
            if (isset($data['rows'][$id])) {
                $data['rows'][$id] = [...$data['rows'][$id], ...$changes, 'id' => $id];
            }

            return $data;
        });
    }

    /**
     * @param callable(array): bool $match
     */
    public function delete(string $table, callable $match): int
    {
        $deleted = 0;

        $this->mutate($table, function (array $data) use ($match, &$deleted) {
            $kept = array_filter($data['rows'], fn (array $row) => !$match($row));
            $deleted = count($data['rows']) - count($kept);
            $data['rows'] = $kept;

            return $data;
        });

        return $deleted;
    }

    public function truncate(string ...$tables): void
    {
        foreach ($tables as $table) {
            $this->mutate($table, fn () => ['next_id' => 1, 'rows' => []]);
        }
    }

    /**
     * @param callable(array{next_id: int, rows: array<int, array>}): array $change
     */
    private function mutate(string $table, callable $change): void
    {
        $file = $this->file($table);

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }

        $handle = fopen($file, 'c+');
        flock($handle, LOCK_EX);

        try {
            $data = $change($this->decode(stream_get_contents($handle)));
            $json = json_encode(
                ['next_id' => $data['next_id'], 'rows' => (object) $data['rows']],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
            );

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json . "\n");
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array{next_id: int, rows: array<int, array>}
     */
    private function decode(string|false $json): array
    {
        if ($json === false || trim($json) === '') {
            return ['next_id' => 1, 'rows' => []];
        }

        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $rows = [];

        foreach ($data['rows'] as $id => $row) {
            $rows[(int) $id] = $row;
        }

        return ['next_id' => (int) $data['next_id'], 'rows' => $rows];
    }

    private function file(string $table): string
    {
        if (!preg_match('/^[a-z_]+$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name {$table}");
        }

        return "{$this->dir}/{$table}.json";
    }
}
