<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;

final class FormFieldTest extends TestCase
{
    private function field(array $data): string
    {
        return render('partials/form-field', ['name' => 'name', 'label' => 'Jméno', 'value' => '', ...$data]);
    }

    public function testErrorMarksFieldInvalidAndIsDescribedBy(): void
    {
        $html = $this->field(['error' => 'Vyplňte jméno.']);

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="field-name-error"', $html);
        $this->assertStringContainsString('id="field-name-error"', $html);
    }

    public function testValueIsEscaped(): void
    {
        $html = $this->field(['value' => '"><script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testPasswordValueIsNeverPrinted(): void
    {
        $html = $this->field(['type' => 'password', 'value' => 'secret']);

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringNotContainsString('secret', $html);
    }
}
