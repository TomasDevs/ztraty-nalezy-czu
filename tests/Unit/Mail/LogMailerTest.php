<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\LogMailer;
use App\Mail\MailMessage;
use PHPUnit\Framework\TestCase;

final class LogMailerTest extends TestCase
{
    public function testAppendsMessageToLogFile(): void
    {
        $file = sys_get_temp_dir() . '/mail-' . uniqid() . '/mail.log';
        $mailer = new LogMailer($file);

        $this->assertTrue($mailer->send(new MailMessage('a@example.com', 'Předmět', 'Tělo zprávy', 'b@example.com')));
        $this->assertTrue($mailer->send(new MailMessage('c@example.com', 'Druhý', 'Další tělo')));

        $log = (string) file_get_contents($file);
        $this->assertStringContainsString('To: a@example.com', $log);
        $this->assertStringContainsString('Reply-To: b@example.com', $log);
        $this->assertStringContainsString('Tělo zprávy', $log);
        $this->assertStringContainsString('To: c@example.com', $log);

        unlink($file);
        rmdir(dirname($file));
    }
}
