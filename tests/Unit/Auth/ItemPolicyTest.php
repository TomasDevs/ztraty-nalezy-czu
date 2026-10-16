<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\ItemPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ItemPolicyTest extends TestCase
{
    private const AUTHOR = ['id' => 1, 'role' => 'user'];
    private const STRANGER = ['id' => 2, 'role' => 'user'];
    private const ADMIN = ['id' => 3, 'role' => 'admin'];

    private static function item(string $status): array
    {
        return ['user_id' => 1, 'status' => $status];
    }

    public static function manageCases(): array
    {
        return [
            'guest' => [null, false],
            'author' => [self::AUTHOR, true],
            'stranger' => [self::STRANGER, false],
            'admin' => [self::ADMIN, true],
        ];
    }

    #[DataProvider('manageCases')]
    public function testCanManage(?array $user, bool $expected): void
    {
        $this->assertSame($expected, ItemPolicy::canManage($user, self::item('active')));
    }

    public static function viewCases(): array
    {
        return [
            'guest sees active' => [null, 'active', true],
            'guest sees returned' => [null, 'returned', true],
            'guest cannot see hidden' => [null, 'hidden', false],
            'stranger cannot see hidden' => [self::STRANGER, 'hidden', false],
            'author sees hidden' => [self::AUTHOR, 'hidden', true],
            'admin sees hidden' => [self::ADMIN, 'hidden', true],
        ];
    }

    #[DataProvider('viewCases')]
    public function testCanView(?array $user, string $status, bool $expected): void
    {
        $this->assertSame($expected, ItemPolicy::canView($user, self::item($status)));
    }

    public static function contactCases(): array
    {
        return [
            'guest' => [null, 'active', false],
            'author' => [self::AUTHOR, 'active', false],
            'stranger' => [self::STRANGER, 'active', true],
            'admin who is not the author' => [self::ADMIN, 'active', true],
            'returned item' => [self::STRANGER, 'returned', false],
            'hidden item' => [self::STRANGER, 'hidden', false],
        ];
    }

    #[DataProvider('contactCases')]
    public function testCanContact(?array $user, string $status, bool $expected): void
    {
        $this->assertSame($expected, ItemPolicy::canContact($user, self::item($status)));
    }
}
