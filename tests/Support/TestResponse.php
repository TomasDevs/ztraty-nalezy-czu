<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Response;
use PHPUnit\Framework\Assert;

final class TestResponse
{
    public function __construct(public readonly Response $response)
    {
    }

    public function status(): int
    {
        return $this->response->status;
    }

    public function body(): string
    {
        return $this->response->body;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertStatus(int $status): self
    {
        Assert::assertSame($status, $this->response->status, 'Unexpected status. Body: ' . mb_substr(strip_tags($this->response->body), 0, 300));

        return $this;
    }

    public function assertRedirect(string $path): self
    {
        Assert::assertNotNull($this->response->location, 'Response is not a redirect, status ' . $this->response->status);
        Assert::assertSame(url($path), $this->response->location);

        return $this;
    }

    public function assertSee(string $text): self
    {
        Assert::assertStringContainsString($text, $this->response->body);

        return $this;
    }

    public function assertDontSee(string $text): self
    {
        Assert::assertStringNotContainsString($text, $this->response->body);

        return $this;
    }
}
