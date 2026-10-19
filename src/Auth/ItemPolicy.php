<?php

declare(strict_types=1);

namespace App\Auth;

final class ItemPolicy
{
    public static function canManage(?array $user, array $item): bool
    {
        return $user !== null && ($user['role'] === 'admin' || $user['id'] === $item['user_id']);
    }

    public static function canView(?array $user, array $item): bool
    {
        return $item['status'] !== 'hidden' || self::canManage($user, $item);
    }

    public static function canContact(?array $user, array $item): bool
    {
        return $user !== null && $user['id'] !== $item['user_id'] && $item['status'] === 'active';
    }
}
