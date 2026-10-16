<?php

declare(strict_types=1);

namespace App\Mail;

final class NativeMailer implements Mailer
{
    public function __construct(private readonly string $from)
    {
    }

    public function send(MailMessage $message): bool
    {
        foreach ([$this->from, $message->to, $message->subject, $message->replyTo ?? ''] as $value) {
            if (preg_match('/[\r\n]/', $value)) {
                throw new \InvalidArgumentException('Line breaks are not allowed in mail headers');
            }
        }

        $headers = [
            "From: {$this->from}",
            'Content-Type: text/plain; charset=UTF-8',
            ...($message->replyTo !== null ? ["Reply-To: {$message->replyTo}"] : []),
        ];

        return mail($message->to, mb_encode_mimeheader($message->subject, 'UTF-8'), $message->body, implode("\r\n", $headers));
    }
}
