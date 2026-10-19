<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\BuildingForm;
use PHPUnit\Framework\TestCase;

final class BuildingFormTest extends TestCase
{
    private function input(array $overrides = []): array
    {
        return ['name' => 'Provozně ekonomická fakulta', 'short' => 'PEF', 'slug' => 'pef', 'lat' => '50.13116', 'lng' => '14.37322', ...$overrides];
    }

    private function errors(array $overrides): array
    {
        $form = BuildingForm::fromArray($this->input($overrides));
        $this->assertFalse($form->validate([]));

        return $form->errors;
    }

    public function testValidInputPassesAndProducesData(): void
    {
        $form = BuildingForm::fromArray($this->input());

        $this->assertTrue($form->validate([]));
        $this->assertSame(
            ['name' => 'Provozně ekonomická fakulta', 'short' => 'PEF', 'slug' => 'pef', 'lat' => 50.13116, 'lng' => 14.37322],
            $form->toData()
        );
    }

    public function testSlugDefaultsToSlugifiedName(): void
    {
        $form = BuildingForm::fromArray($this->input(['slug' => '', 'name' => 'Velká studovna']));

        $this->assertTrue($form->validate([]));
        $this->assertSame('velka-studovna', $form->toData()['slug']);
    }

    public function testTextLimits(): void
    {
        $this->assertArrayHasKey('name', $this->errors(['name' => 'a']));
        $this->assertArrayHasKey('name', $this->errors(['name' => str_repeat('a', 151)]));
        $this->assertArrayHasKey('short', $this->errors(['short' => '']));
        $this->assertArrayHasKey('short', $this->errors(['short' => str_repeat('a', 21)]));
    }

    public function testSlugMustBeFree(): void
    {
        $form = BuildingForm::fromArray($this->input());

        $this->assertFalse($form->validate(['pef']));
        $this->assertArrayHasKey('slug', $form->errors);
    }

    public function testCoordinatesMustBeNumbersOnCampus(): void
    {
        $this->assertArrayHasKey('lat', $this->errors(['lat' => 'abc']));
        $this->assertArrayHasKey('lat', $this->errors(['lat' => '48.2', 'lng' => '16.37']));
        $this->assertArrayHasKey('lat', $this->errors(['lng' => '']));
    }
}
