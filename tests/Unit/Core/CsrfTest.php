<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Csrf;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    public function testTokenIsStablePerSession(): void
    {
        $data = [];
        $csrf = new Csrf(new Session($data));

        $this->assertSame($csrf->token(), $csrf->token());
        $this->assertSame(64, strlen($csrf->token()));
    }

    public function testChecksToken(): void
    {
        $data = [];
        $csrf = new Csrf(new Session($data));

        $this->assertTrue($csrf->check($csrf->token()));
        $this->assertFalse($csrf->check(null));
        $this->assertFalse($csrf->check(''));
        $this->assertFalse($csrf->check('forged'));
    }

    public function testTokensDifferBetweenSessions(): void
    {
        $a = [];
        $b = [];

        $this->assertNotSame((new Csrf(new Session($a)))->token(), (new Csrf(new Session($b)))->token());
    }
}
