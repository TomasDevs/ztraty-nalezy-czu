<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Auth\Auth;
use App\Auth\LoginResult;
use App\Core\Session;
use App\Repositories\UserRepository;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class AuthTest extends DatabaseTestCase
{
    use Fixtures;

    private array $sessionData = [];
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionData = [];
        $this->users = new UserRepository(self::$db);
    }

    private function auth(): Auth
    {
        return new Auth(new Session($this->sessionData), $this->users);
    }

    public function testCorrectPasswordLogsIn(): void
    {
        $user = $this->createUser();
        $auth = $this->auth();

        $this->assertSame(LoginResult::Success, $auth->attempt($user['email'], 'password123'));
        $this->assertTrue($auth->check());
        $this->assertSame($user['id'], $auth->user()['id']);
    }

    public function testEmailIsMatchedCaseInsensitively(): void
    {
        $user = $this->createUser(['email' => 'jan@example.com']);

        $this->assertSame(LoginResult::Success, $this->auth()->attempt('JAN@Example.com', 'password123'));
    }

    public function testWrongPasswordOrUnknownEmailIsInvalid(): void
    {
        $user = $this->createUser();
        $auth = $this->auth();

        $this->assertSame(LoginResult::Invalid, $auth->attempt($user['email'], 'wrong-password'));
        $this->assertSame(LoginResult::Invalid, $auth->attempt('nobody@example.com', 'password123'));
        $this->assertFalse($auth->check());
    }

    public function testBlockedUserCannotLogIn(): void
    {
        $user = $this->createUser(['is_blocked' => 1]);
        $auth = $this->auth();

        $this->assertSame(LoginResult::Blocked, $auth->attempt($user['email'], 'password123'));
        $this->assertFalse($auth->check());
    }

    public function testBlockedPasswordIsNotRevealedWithWrongPassword(): void
    {
        $user = $this->createUser(['is_blocked' => 1]);

        $this->assertSame(LoginResult::Invalid, $this->auth()->attempt($user['email'], 'wrong-password'));
    }

    public function testUserBlockedAfterLoginIsLoggedOut(): void
    {
        $user = $this->createUser();
        $this->auth()->attempt($user['email'], 'password123');

        $this->users->setBlocked($user['id'], true);
        $nextRequest = $this->auth();

        $this->assertNull($nextRequest->user());
        $this->assertArrayNotHasKey('user_id', $this->sessionData);
    }

    public function testDeletedUserIsLoggedOut(): void
    {
        $user = $this->createUser();
        $this->auth()->attempt($user['email'], 'password123');

        self::$db->exec("DELETE FROM users WHERE id = {$user['id']}");

        $this->assertFalse($this->auth()->check());
    }

    public function testLogoutClearsUser(): void
    {
        $user = $this->createUser();
        $auth = $this->auth();
        $auth->attempt($user['email'], 'password123');

        $auth->logout();

        $this->assertFalse($auth->check());
        $this->assertNull($auth->user());
    }

    public function testIsAdmin(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $user = $this->createUser();

        $auth = $this->auth();
        $this->assertFalse($auth->isAdmin());

        $auth->login($user);
        $this->assertFalse($auth->isAdmin());

        $auth->login($admin);
        $this->assertTrue($auth->isAdmin());
    }
}
