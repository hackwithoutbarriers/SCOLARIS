<?php

namespace App\Services;

final class StudentImportNormalizer
{
    public static function normalizeName(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    /** @return array{value: ?string, warning: ?string} */
    public static function normalizeGender(string $value): array
    {
        $gender = mb_strtolower(trim($value));

        if ($gender === '') {
            return ['value' => null, 'warning' => null];
        }

        if (in_array($gender, ['m', 'masculin', 'homme', 'garçon'], true)) {
            return ['value' => 'male', 'warning' => null];
        }

        if (in_array($gender, ['f', 'féminin', 'feminin', 'femme', 'fille'], true)) {
            return ['value' => 'female', 'warning' => null];
        }

        return ['value' => null, 'warning' => 'gender_unrecognized'];
    }

    /** @return array{value: ?string, warning: ?string, error: ?string} */
    public static function normalizeDate(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['value' => null, 'warning' => null, 'error' => null];
        }

        if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $value)) {
            return ['value' => null, 'warning' => 'date_two_digit_year', 'error' => null];
        }

        $formats = [
            ['!Y-m-d', '/^\d{4}-\d{2}-\d{2}$/'],
            ['!d/m/Y', '/^\d{2}\/\d{2}\/\d{4}$/'],
            ['!d-m-Y', '/^\d{2}-\d{2}-\d{4}$/'],
        ];

        foreach ($formats as [$format, $pattern]) {
            if (! preg_match($pattern, $value)) {
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                return ['value' => null, 'warning' => null, 'error' => 'date_invalid'];
            }

            return ['value' => $date->format('Y-m-d'), 'warning' => null, 'error' => null];
        }

        return ['value' => null, 'warning' => null, 'error' => 'date_unrecognized'];
    }

    /** @return array{value: ?string, error: ?string} */
    public static function normalizePhone(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['value' => null, 'error' => 'phone_missing'];
        }

        if (preg_match('/[;,\/\r\n]/', $value)) {
            return ['value' => null, 'error' => 'phone_multiple_numbers'];
        }

        $phone = PhoneNumberFormatter::toE164($value);

        return $phone === null
            ? ['value' => null, 'error' => 'phone_invalid']
            : ['value' => $phone, 'error' => null];
    }
}
