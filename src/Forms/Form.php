<?php

declare(strict_types=1);

namespace App\Forms;

abstract class Form
{
    /** @var array<string, string> */
    public array $errors = [];

    public function value(string $field): string
    {
        $property = lcfirst(str_replace('_', '', ucwords($field, '_')));

        return (string) ($this->$property ?? '');
    }

    protected static function string(array $input, string $key): string
    {
        return is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    }

    /**
     * @param array<string, string|null> $rules Field name => error message, null when the field is valid
     */
    protected function check(array $rules): bool
    {
        $this->errors = array_filter($rules);

        return $this->errors === [];
    }

    protected static function inCampus(float $lat, float $lng): bool
    {
        return $lat >= 50.125 && $lat <= 50.135 && $lng >= 14.365 && $lng <= 14.380;
    }
}
