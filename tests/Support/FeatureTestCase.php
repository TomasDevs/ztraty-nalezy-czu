<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\App;
use App\Core\Kernel;
use App\Core\Session;

abstract class FeatureTestCase extends DatabaseTestCase
{
    use Fixtures;

    protected array $session = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = [];
        App::reset();
        App::set('db', self::$db);
        App::set('session', new Session($this->session));
    }

    protected function get(string $path, array $query = []): TestResponse
    {
        return $this->request('GET', $path, $query);
    }

    protected function post(string $path, array $data = [], bool $withToken = true): TestResponse
    {
        return $this->request('POST', $path, [], $withToken ? ['_token' => App::csrf()->token(), ...$data] : $data);
    }

    protected function actingAs(array $user): void
    {
        $this->session['user_id'] = $user['id'];
    }

    private function request(string $method, string $path, array $query = [], array $data = []): TestResponse
    {
        $_GET = $query;
        $_POST = $data;
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path . ($query ? '?' . http_build_query($query) : '');

        return new TestResponse(Kernel::handle($method, $path));
    }
}
