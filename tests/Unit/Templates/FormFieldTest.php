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

    public function testSelectMarksSelectedOption(): void
    {
        $html = $this->field([
            'type' => 'select',
            'value' => 'pef',
            'options' => [['slug' => 'pef', 'name' => 'PEF'], ['slug' => 'fzp', 'name' => 'FŽP <b>']],
        ]);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('<option value="">Vyberte…</option>', $html);
        $this->assertMatchesRegularExpression('/<option value="pef"\s+selected>/', $html);
        $this->assertStringContainsString('FŽP &lt;b&gt;', $html);
    }

    public function testDateFieldDefaultsMaxToToday(): void
    {
        $html = $this->field(['type' => 'date', 'value' => '2026-10-01']);

        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('max="' . date('Y-m-d') . '"', $html);
    }

    public function testFileFieldHasAcceptAndNoValue(): void
    {
        $html = $this->field(['type' => 'file', 'value' => 'leak', 'attributes' => ['accept' => 'image/jpeg,image/png']]);

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('accept="image/jpeg,image/png"', $html);
        $this->assertStringNotContainsString('leak', $html);
    }

    public function testOptionalFieldIsNotRequired(): void
    {
        $this->assertStringContainsString(' required', $this->field([]));
        $this->assertStringNotContainsString(' required', $this->field(['required' => false]));
    }

    public function testTextareaKeepsEscapedValue(): void
    {
        $html = $this->field(['type' => 'textarea', 'value' => 'a <i>b</i>']);

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('a &lt;i&gt;b&lt;/i&gt;', $html);
    }
}
