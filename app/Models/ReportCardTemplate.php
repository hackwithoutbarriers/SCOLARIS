<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ReportCardTemplate extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = ['school_id', 'name', 'version', 'status', 'orientation', 'schema', 'active'];
    protected function casts(): array { return ['schema' => 'array', 'active' => 'boolean']; }

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            self::validateSchema($template->schema);
            $template->status ??= 'DRAFT';
            $template->orientation ??= $template->schema['page']['orientation'] ?? 'portrait';
            if (!in_array($template->status, ['DRAFT', 'ACTIVE', 'ARCHIVED'], true)) {
                throw ValidationException::withMessages(['status' => 'Unsupported template status.']);
            }
            if (!in_array($template->orientation, ['portrait', 'landscape'], true)) {
                throw ValidationException::withMessages(['orientation' => 'Orientation must be portrait or landscape.']);
            }
        });
        static::saved(function (self $template): void {
            if ($template->status === 'ACTIVE') {
                self::query()->where('school_id', $template->school_id)->where('id', '<>', $template->id)->where('status', 'ACTIVE')->update(['status' => 'ARCHIVED', 'active' => false]);
                if (!$template->active) {
                    self::withoutEvents(fn () => $template->update(['active' => true]));
                }
            }
        });
    }

    public static function defaultSchema(): array
    {
        return [
            'page' => ['orientation' => 'portrait', 'margins' => ['top' => 12, 'right' => 12, 'bottom' => 12, 'left' => 12]],
            'header' => ['enabled' => true, 'logo' => true, 'school_name' => true, 'address' => true, 'phone' => true, 'custom_text' => null],
            'student' => ['fields' => ['student_number', 'name'], 'layout' => 'inline'],
            'subjects' => ['columns' => ['subject', 'average', 'grade_letter'], 'ordering' => 'display_order', 'visibility' => true],
            'summary' => ['average' => true, 'ranking' => true, 'coefficients' => true, 'absences' => true, 'show_attendance' => true],
            'footer' => ['enabled' => true, 'signatures' => [], 'custom_text' => null],
            'sections' => ['header', 'subjects', 'summary', 'appreciation'],
            'styles' => ['primary_color' => '#1E3A8A', 'secondary_color' => '#10B981', 'font_family' => 'Roboto', 'font_size' => 10, 'title' => 'Bulletin scolaire'],
        ];
    }

    public static function validateSchema(?array $schema): void
    {
        if (!is_array($schema) || !is_array($schema['sections'] ?? null) || $schema['sections'] === []) {
            throw ValidationException::withMessages(['schema' => 'A template must define at least one section.']);
        }
        if (isset($schema['page']['orientation']) && !in_array($schema['page']['orientation'], ['portrait', 'landscape'], true)) {
            throw ValidationException::withMessages(['schema' => 'Page orientation must be portrait or landscape.']);
        }
        if (isset($schema['styles']['font_size']) && (!is_numeric($schema['styles']['font_size']) || (float) $schema['styles']['font_size'] < 7 || (float) $schema['styles']['font_size'] > 24)) {
            throw ValidationException::withMessages(['schema' => 'Font size must be between 7 and 24.']);
        }
        foreach ($schema['sections'] as $section) {
            if (!is_string($section) || !in_array($section, ['subjects', 'summary', 'appreciation', 'attendance', 'header'], true)) {
                throw ValidationException::withMessages(['schema' => 'Template sections contain an unsupported value.']);
            }
        }
    }
}
