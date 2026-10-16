<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Mail\MailMessage;
use App\Mail\Mailer;

final class FakeMailer implements Mailer
{
    /** @var MailMessage[] */
    public array $sent = [];
    public bool $succeeds = true;

    public function send(MailMessage $message): bool
    {
        $this->sent[] = $message;

        return $this->succeeds;
    }
}
