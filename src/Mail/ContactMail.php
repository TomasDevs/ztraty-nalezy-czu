<?php

declare(strict_types=1);

namespace App\Mail;

final class ContactMail
{
    public static function create(array $item, array $author, array $sender, string $body): MailMessage
    {
        $code = item_code($item);
        $title = preg_replace('/\s+/', ' ', $item['title']);

        $text = implode("\n\n", [
            'Dobrý den,',
            "{$sender['name']} vám napsal(a) k inzerátu „{$title}“ ({$code}):",
            $body,
            'Odpovědět můžete přímo na tento e-mail.',
            'Inzerát: ' . absolute_url(item_path($item)),
        ]);

        return new MailMessage($author['email'], "Zpráva k inzerátu {$code}: {$title}", "{$text}\n", $sender['email']);
    }
}
