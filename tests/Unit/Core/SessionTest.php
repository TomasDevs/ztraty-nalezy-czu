<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public function testWritesGoToTheUnderlyingArray(): void
    {
        $data = [];
        $session = new Session($data);

        $session->set('user_id', 5);

        $this->assertSame(['user_id' => 5], $data);
        $this->assertSame(5, $session->get('user_id'));
        $this->assertTrue($session->has('user_id'));
    }

    public function testGetFallsBackToDefaultAndRemoveDeletes(): void
    {
        $data = ['a' => 1];
        $session = new Session($data);

        $this->assertSame('x', $session->get('missing', 'x'));

        $session->remove('a');

        $this->assertFalse($session->has('a'));
    }

    public function testFlashMessagesAreReturnedOnce(): void
    {
        $data = [];
        $session = new Session($data);

        $session->flash('success', 'Saved');
        $session->flash('error', 'Failed');

        $this->assertSame([['success', 'Saved'], ['error', 'Failed']], $session->pullFlashes());
        $this->assertSame([], $session->pullFlashes());
    }

    public function testRegenerateIsSafeWithoutActiveSession(): void
    {
        $data = [];

        (new Session($data))->regenerate();

        $this->assertSame([], $data);
    }
}
