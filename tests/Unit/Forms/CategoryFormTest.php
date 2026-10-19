<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\CategoryForm;
use PHPUnit\Framework\TestCase;

final class CategoryFormTest extends TestCase
{
    public function testValidInputPasses(): void
    {
        $form = CategoryForm::fromArray(['name' => 'Šperky a doplňky', 'slug' => 'doplnky']);

        $this->assertTrue($form->validate([]));
        $this->assertSame('doplnky', $form->slug());
    }

    public function testSlugDefaultsToSlugifiedName(): void
    {
        $form = CategoryForm::fromArray(['name' => 'Šperky a doplňky']);

        $this->assertTrue($form->validate([]));
        $this->assertSame('sperky-a-doplnky', $form->slug());
    }

    public function testToData(): void
    {
        $form = CategoryForm::fromArray(['name' => 'Klíče']);

        $this->assertSame(['name' => 'Klíče', 'slug' => 'klice'], $form->toData());
    }

    public function testNameLength(): void
    {
        $this->assertArrayHasKey('name', $this->errors(['name' => 'a']));
        $this->assertArrayHasKey('name', $this->errors(['name' => str_repeat('a', 101)]));
    }

    public function testSlugMustBeUrlSafe(): void
    {
        $this->assertArrayHasKey('slug', $this->errors(['name' => 'Klíče', 'slug' => 'Špatný slug']));
        $this->assertArrayHasKey('slug', $this->errors(['name' => 'Klíče', 'slug' => '-klice-']));
        $this->assertArrayHasKey('slug', $this->errors(['name' => 'Klíče', 'slug' => str_repeat('a', 51)]));
    }

    public function testSlugMustBeFree(): void
    {
        $form = CategoryForm::fromArray(['name' => 'Klíče']);

        $this->assertFalse($form->validate(['klice']));
        $this->assertArrayHasKey('slug', $form->errors);
    }

    public function testNameThatSlugifiesToNothingIsRejected(): void
    {
        $this->assertArrayHasKey('slug', $this->errors(['name' => '!!']));
    }

    private function errors(array $input): array
    {
        $form = CategoryForm::fromArray($input);
        $this->assertFalse($form->validate([]));

        return $form->errors;
    }
}
