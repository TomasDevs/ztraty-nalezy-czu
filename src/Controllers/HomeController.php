<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

final class HomeController extends Controller
{
    public function index(): void
    {
        view('pages/home', [
            'title' => 'Ztráty a nálezy na kampusu ČZU',
            'description' => 'Ztratili jste na ČZU ISIC, klíče nebo bundu? Prohlédněte si nalezené věci z celého kampusu v Suchdole, nebo nahlaste ztrátu.',
            'latest' => App::items()->latest(6),
            'stats' => App::items()->stats(),
            'jsonLd' => [[
                '@type' => 'WebSite',
                'name' => 'Ztráty a nálezy ČZU',
                'url' => absolute_url('/'),
                'inLanguage' => 'cs',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => absolute_url('/inzeraty') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ]],
        ]);
    }
}
