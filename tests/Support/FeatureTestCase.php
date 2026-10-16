<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\App;
use App\Core\Kernel;
use App\Core\Session;
use App\Uploads\PhotoProcessor;

abstract class FeatureTestCase extends DatabaseTestCase
{
    use Fixtures;

    protected array $session = [];
    protected string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = [];
        $this->publicDir = sys_get_temp_dir() . '/feature-' . uniqid();
        mkdir($this->publicDir, 0777, true);
        $this->registerServices();
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->publicDir));
        parent::tearDown();
    }

    protected function get(string $path, array $query = []): TestResponse
    {
        return $this->request('GET', $path, $query);
    }

    protected function post(string $path, array $data = [], bool $withToken = true, array $files = []): TestResponse
    {
        return $this->request('POST', $path, [], $withToken ? ['_token' => App::csrf()->token(), ...$data] : $data, $files);
    }

    protected function actingAs(array $user): void
    {
        $this->session['user_id'] = $user['id'];
        $this->registerServices();
    }

    protected function asGuest(): void
    {
        unset($this->session['user_id']);
        $this->registerServices();
    }

    private function registerServices(): void
    {
        App::reset();
        App::set('db', self::$db);
        App::set('session', new Session($this->session));
        App::set('photos', new PhotoProcessor($this->publicDir));
    }

    private function request(string $method, string $path, array $query = [], array $data = [], array $files = []): TestResponse
    {
        $_GET = $query;
        $_POST = $data;
        $_FILES = $files;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path . ($query ? '?' . http_build_query($query) : '');

        return new TestResponse(Kernel::handle($method, $path));
    }
}
