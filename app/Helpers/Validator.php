<?php
declare(strict_types=1);

namespace App\Helpers;

class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $label = ''): static
    {
        if ($value === null || trim((string)$value) === '') {
            $this->errors[$field] = ($label ?: ucfirst($field)) . ' is required.';
        }
        return $this;
    }

    public function email(string $field, string $value): static
    {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Please enter a valid email address.';
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min, string $label = ''): static
    {
        if (mb_strlen($value) < $min) {
            $this->errors[$field] = ($label ?: ucfirst($field)) . " must be at least $min characters.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max, string $label = ''): static
    {
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = ($label ?: ucfirst($field)) . " must not exceed $max characters.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return array_values($this->errors)[0] ?? '';
    }
}
