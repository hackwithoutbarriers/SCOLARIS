<?php

namespace App\Services;

use App\Models\StudentImportBatch;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StudentImportReportService
{
    public function download(StudentImportBatch $batch): StreamedResponse
    {
        return response()->streamDownload(function () use ($batch): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new \RuntimeException('Le rapport n’a pas pu être généré.');
            }

            fputcsv($stream, ['Ligne', 'Code', 'Champ', 'Gravité', 'Problème', 'Valeur à corriger'], ';', '"', '');
            foreach ($batch->rows()->whereIn('status', ['error', 'needs_review'])->orderBy('row_number')->cursor() as $row) {
                foreach ($row->issues_json ?? [] as $issue) {
                    if (($issue['severity'] ?? null) === 'info') {
                        continue;
                    }
                    fputcsv($stream, [
                        (string) $row->row_number,
                        self::safeCell((string) ($issue['code'] ?? '')),
                        self::safeCell((string) ($issue['field'] ?? '')),
                        self::safeCell((string) ($issue['severity'] ?? '')),
                        self::safeCell((string) ($issue['message'] ?? '')),
                        self::safeCell((string) ($issue['value'] ?? '')),
                    ], ';', '"', '');
                }
            }
            fclose($stream);
        }, 'rapport-import-'.(int) $batch->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function safeCell(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
