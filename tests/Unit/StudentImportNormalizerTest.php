<?php

namespace Tests\Unit;

use App\Services\StudentImportNormalizer;
use PHPUnit\Framework\TestCase;

class StudentImportNormalizerTest extends TestCase
{
    public function test_name_normalization_collapses_spacing_and_preserves_accents_and_case(): void
    {
        $this->assertSame('Aïssi Koffi', StudentImportNormalizer::normalizeName("  Aïssi \t Koffi  "));
    }

    public function test_gender_normalization_accepts_common_french_values(): void
    {
        $this->assertSame(['value' => 'male', 'warning' => null], StudentImportNormalizer::normalizeGender('Masculin'));
        $this->assertSame(['value' => 'female', 'warning' => null], StudentImportNormalizer::normalizeGender('f'));
        $this->assertSame(['value' => null, 'warning' => null], StudentImportNormalizer::normalizeGender(''));
        $this->assertSame(['value' => null, 'warning' => 'gender_unrecognized'], StudentImportNormalizer::normalizeGender('inconnu'));
    }

    public function test_date_normalization_accepts_deterministic_dates_and_reports_ambiguities(): void
    {
        $this->assertSame(
            ['value' => '2014-05-12', 'warning' => null, 'error' => null],
            StudentImportNormalizer::normalizeDate('12/05/2014'),
        );
        $this->assertSame(
            ['value' => null, 'warning' => 'date_two_digit_year', 'error' => null],
            StudentImportNormalizer::normalizeDate('12-05-14'),
        );
        $this->assertSame(
            ['value' => null, 'warning' => null, 'error' => 'date_invalid'],
            StudentImportNormalizer::normalizeDate('31/02/2014'),
        );
        $this->assertSame(
            ['value' => null, 'warning' => null, 'error' => 'date_unrecognized'],
            StudentImportNormalizer::normalizeDate('quinze juin 2014'),
        );
    }

    public function test_phone_normalization_reuses_togo_e164_rules_and_rejects_ambiguous_values(): void
    {
        foreach (['+228 90 12 34 56', '90123456', '0022890123456', '90.12.34.56', '90-12-34-56'] as $phone) {
            $this->assertSame(
                ['value' => '+22890123456', 'error' => null],
                StudentImportNormalizer::normalizePhone($phone),
            );
        }

        $this->assertSame(
            ['value' => null, 'error' => 'phone_multiple_numbers'],
            StudentImportNormalizer::normalizePhone('90 12 34 56 / 91 12 34 56'),
        );
        $this->assertSame(
            ['value' => null, 'error' => 'phone_invalid'],
            StudentImportNormalizer::normalizePhone('900025'),
        );
    }
}
