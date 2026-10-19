<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private array $data;

    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    public static function start(): void
    {
        session_name('ztraty_session');
        session_set_cookie_params([
            'path' => base_path() ?: '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function flash(string $type, string $message): void
    {
        $this->data['_flash'][] = [$type, $message];
    }

    /**
     * @return array{0: string, 1: string}[]
     */
    public function pullFlashes(): array
    {
        $flashes = $this->data['_flash'] ?? [];
        unset($this->data['_flash']);

        return $flashes;
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
