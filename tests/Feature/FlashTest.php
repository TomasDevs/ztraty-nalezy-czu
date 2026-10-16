<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class FlashTest extends FeatureTestCase
{
    public function testFlashMessageIsShownOnce(): void
    {
        App::session()->flash('success', 'Uloženo <b>ok</b>');

        $this->get('/')->assertSee('alert--success')->assertSee('Uloženo &lt;b&gt;ok&lt;/b&gt;');
        $this->get('/')->assertDontSee('alert--success');
    }
}
