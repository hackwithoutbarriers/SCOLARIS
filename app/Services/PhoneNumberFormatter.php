<?php

namespace App\Services;

class PhoneNumberFormatter
{
    public static function toE164(string $raw, string $defaultCountry = 'TG'): ?string
    {
        $value = preg_replace('/[\s().-]+/', '', trim($raw)) ?? '';
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        } elseif (! str_starts_with($value, '+')) {
            $value = ($defaultCountry === 'TG' ? '+228' : '').ltrim($value, '0');
        }

        return preg_match('/^\+228[79]\d{7}$/', $value) ? $value : null;
    }
}
