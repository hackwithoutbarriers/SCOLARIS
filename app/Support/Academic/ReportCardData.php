<?php

namespace App\Support\Academic;

use InvalidArgumentException;

/**
 * Stable, renderer-independent report card payload.
 */
final class ReportCardData implements \JsonSerializable
{
    public function __construct(
        public readonly array $student,
        public readonly array $period,
        public readonly array $subjects,
        public readonly array $summary,
        public readonly array $appreciation = [],
        public readonly array $academicMention = [],
        public readonly array $conduct = [],
        public readonly array $attendance = [],
        public readonly array $attendanceSummary = [],
        public readonly array $school = [],
        public readonly array $class = [],
        public readonly array $ranking = [],
        public readonly array $signatures = [],
        public readonly array $decision = [],
        public readonly int $schemaVersion = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        foreach (['student', 'period', 'subjects', 'summary'] as $key) {
            if (!array_key_exists($key, $data) || !is_array($data[$key])) {
                throw new InvalidArgumentException("Report card data requires a {$key} object.");
            }
        }

        return new self(
            student: $data['student'],
            period: $data['period'],
            subjects: array_values($data['subjects']),
            summary: $data['summary'],
            appreciation: is_array($data['appreciation'] ?? null) ? $data['appreciation'] : [],
            academicMention: is_array($data['academic_mention'] ?? null) ? $data['academic_mention'] : [],
            conduct: is_array($data['conduct'] ?? null) ? $data['conduct'] : [],
            attendance: is_array($data['attendance'] ?? null) ? $data['attendance'] : (is_array($data['attendance_summary'] ?? null) ? $data['attendance_summary'] : []),
            attendanceSummary: is_array($data['attendance_summary'] ?? null) ? $data['attendance_summary'] : (is_array($data['attendance'] ?? null) ? $data['attendance'] : []),
            school: is_array($data['school_identity'] ?? null) ? $data['school_identity'] : (is_array($data['school'] ?? null) ? $data['school'] : []),
            class: is_array($data['class'] ?? null) ? $data['class'] : [],
            ranking: is_array($data['ranking'] ?? null) ? $data['ranking'] : [],
            signatures: is_array($data['signatures'] ?? null) ? $data['signatures'] : [],
            decision: is_array($data['decision'] ?? null) ? $data['decision'] : [],
            schemaVersion: (int) ($data['schema_version'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'student' => $this->student,
            'student_identity' => $this->student,
            'school_identity' => $this->school,
            'period' => $this->period,
            'class' => $this->class,
            'subjects' => $this->subjects,
            'summary' => $this->summary,
            'appreciation' => $this->appreciation,
            'academic_mention' => $this->academicMention,
            'conduct' => $this->conduct,
            'attendance' => $this->attendance,
            'attendance_summary' => $this->attendanceSummary,
            'ranking' => $this->ranking,
            'signatures' => $this->signatures,
            'decision' => $this->decision,
        ];
    }

    public function jsonSerialize(): array { return $this->toArray(); }
}
