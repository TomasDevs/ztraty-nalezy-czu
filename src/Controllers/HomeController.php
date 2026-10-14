<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController extends Controller
{
    public function index(): void
    {
        view('pages/home', [
            'title' => 'Ztráty a nálezy na kampusu ČZU',
            'description' => 'Ztratili jste na ČZU ISIC, klíče nebo bundu? Prohlédněte si nalezené věci z celého kampusu v Suchdole, nebo nahlaste ztrátu.',
            'latest' => $this->items()->latest(6),
        ]);
    }
}
