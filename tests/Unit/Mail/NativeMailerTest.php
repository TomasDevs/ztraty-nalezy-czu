<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\MailMessage;
use App\Mail\NativeMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeMailerTest extends TestCase
{
    public static function injections(): array
    {
        return [
            'to' => [new MailMessage("a@example.com\nBcc: x@example.com", 'Předmět', 'Tělo')],
            'subject' => [new MailMessage('a@example.com', "Předmět\r\nBcc: x@example.com", 'Tělo')],
            'reply-to' => [new MailMessage('a@example.com', 'Předmět', 'Tělo', "b@example.com\nBcc: x@example.com")],
        ];
    }

    #[DataProvider('injections')]
    public function testHeaderInjectionIsRejected(MailMessage $message): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new NativeMailer('noreply@example.com'))->send($message);
    }
}
