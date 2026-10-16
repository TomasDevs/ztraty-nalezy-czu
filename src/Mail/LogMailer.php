<?php

declare(strict_types=1);

namespace App\Mail;

final class LogMailer implements Mailer
{
    public function __construct(private readonly string $file)
    {
    }

    public function send(MailMessage $message): bool
    {
        if (!is_dir(dirname($this->file))) {
            mkdir(dirname($this->file), 0775, true);
        }

        $entry = sprintf(
            "[%s]\nTo: %s\n%sSubject: %s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            $message->to,
            $message->replyTo !== null ? "Reply-To: {$message->replyTo}\n" : '',
            $message->subject,
            $message->body
        );

        return file_put_contents($this->file, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
