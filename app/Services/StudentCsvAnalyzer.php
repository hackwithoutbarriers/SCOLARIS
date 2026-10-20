<?php

namespace App\Services;

use Generator;
use InvalidArgumentException;
use RuntimeException;

final class StudentCsvAnalyzer
{
    private const DELIMITERS = [',', ';', "\t"];

    public function __construct(private readonly array $synonyms = []) {}

    /**
     * @return array{
     *     delimiter: string,
     *     header_row: int,
     *     headers: array<int, string>,
     *     mapping: array<string, array{field: string, label: string, column_index: int, confidence: float}>,
     *     ignored_columns: array<int, string>,
     *     total_rows: int,
     *     preview: array<int, array{row: int, values: array<string, string>}>
     * }
     */
    public function analyze(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('Le fichier CSV est introuvable ou illisible.');
        }

        $size = filesize($path);
        if ($size === false || $size === 0) {
            throw new InvalidArgumentException('Le fichier CSV est vide.');
        }
        if ($size > config('student-import.max_file_size', 5 * 1024 * 1024)) {
            throw new InvalidArgumentException('Le fichier dépasse la taille maximale autorisée.');
        }

        $signature = file_get_contents($path, false, null, 0, 4);
        if (str_starts_with((string) $signature, "PK\x03\x04")) {
            throw new InvalidArgumentException('Le format XLSX n’est pas pris en charge. Enregistrez le fichier en CSV UTF-8.');
        }

        $this->assertUtf8($path);

        $candidate = $this->detectHeader($path);
        if ($candidate === null) {
            throw new InvalidArgumentException('Aucune ligne d’en-tête reconnue. Vérifiez les colonnes Nom et Prénom.');
        }

        return $this->countAndPreview($path, $candidate);
    }

    /** @return Generator<int, array{row: int, values: array<int, string>}> */
    public function records(string $path, string $delimiter, int $headerRow): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier CSV.');
        }

        try {
            $record = 0;
            while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $record++;
                if ($record <= $headerRow || $this->isBlankRow($values)) {
                    continue;
                }

                yield ['row' => $record, 'values' => array_map(static fn (mixed $value): string => trim((string) $value), $values)];
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return array<int, array{row: int, values: array<string, string>}> */
    public function previewForMapping(string $path, string $delimiter, int $headerRow, array $mapping): array
    {
        $preview = [];
        foreach ($this->records($path, $delimiter, $headerRow) as $record) {
            $values = [];
            foreach ($mapping as $field => $column) {
                $values[$field] = trim((string) ($record['values'][$column['column_index']] ?? ''));
            }
            $preview[] = ['row' => $record['row'], 'values' => $values];
            if (count($preview) === 20) {
                break;
            }
        }

        return $preview;
    }

    /** @return array{delimiter: string, header_row: int, headers: array<int, string>, mapping: array<string, array{field: string, label: string, column_index: int, confidence: float}>}|null */
    private function detectHeader(string $path): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach (self::DELIMITERS as $delimiter) {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Impossible de lire le fichier CSV.');
            }

            try {
                for ($record = 1; $record <= 50 && ($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false; $record++) {
                    if ($record === 1 && isset($row[0])) {
                        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]) ?? (string) $row[0];
                    }

                    $headers = array_map(static fn (mixed $value): string => trim((string) $value), $row);
                    $mapping = $this->mapHeaders($headers);
                    $score = count($mapping);

                    if (! isset($mapping['last_name'], $mapping['first_name'])) {
                        continue;
                    }
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = [
                            'delimiter' => $delimiter,
                            'header_row' => $record,
                            'headers' => $headers,
                            'mapping' => $mapping,
                        ];
                    }
                }
            } finally {
                fclose($handle);
            }
        }

        return $best;
    }

    /**
     * @param  array{delimiter: string, header_row: int, headers: array<int, string>, mapping: array<string, array{field: string, label: string, column_index: int, confidence: float}>}  $candidate
     * @return array{delimiter: string, header_row: int, headers: array<int, string>, mapping: array<string, array{field: string, label: string, column_index: int, confidence: float}>, ignored_columns: array<int, string>, total_rows: int, preview: array<int, array{row: int, values: array<string, string>}>}
     */
    private function countAndPreview(string $path, array $candidate): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier CSV.');
        }

        $totalRows = 0;
        $preview = [];
        $record = 0;

        try {
            while (($row = fgetcsv($handle, null, $candidate['delimiter'], '"', '')) !== false) {
                $record++;
                if ($record <= $candidate['header_row'] || $this->isBlankRow($row)) {
                    continue;
                }

                $totalRows++;
                if ($totalRows > config('student-import.max_rows', 2000)) {
                    throw new InvalidArgumentException('Le fichier dépasse le plafond de lignes autorisé.');
                }

                if (count($preview) >= 20) {
                    continue;
                }

                $values = [];
                foreach ($candidate['mapping'] as $field => $column) {
                    $values[$field] = trim((string) ($row[$column['column_index']] ?? ''));
                }
                $preview[] = ['row' => $record, 'values' => $values];
            }
        } finally {
            fclose($handle);
        }

        if ($totalRows === 0) {
            throw new InvalidArgumentException('Le CSV contient un en-tête, mais aucune ligne d’élève à importer.');
        }

        $mappedColumns = array_column($candidate['mapping'], 'column_index');
        $ignoredColumns = [];
        foreach ($candidate['headers'] as $index => $header) {
            if (! in_array($index, $mappedColumns, true)) {
                $ignoredColumns[] = $header;
            }
        }

        return [
            ...$candidate,
            'ignored_columns' => $ignoredColumns,
            'total_rows' => $totalRows,
            'preview' => $preview,
        ];
    }

    /** @param array<int, string> $headers
     * @return array<string, array{field: string, label: string, column_index: int, confidence: float}>
     */
    private function mapHeaders(array $headers): array
    {
        $synonyms = $this->synonyms !== [] ? $this->synonyms : config('student-import.synonyms', []);
        $normalizedAliases = [];

        foreach ($synonyms as $field => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAliases[self::normalizeHeader($alias)] = $field;
            }
        }

        $mapping = [];
        foreach ($headers as $index => $header) {
            $normalized = self::normalizeHeader($header);
            $field = $normalizedAliases[$normalized] ?? null;
            if ($field === null || isset($mapping[$field])) {
                continue;
            }

            $mapping[$field] = [
                'field' => $field,
                'label' => $header,
                'column_index' => $index,
                'confidence' => 0.98,
            ];
        }

        return $mapping;
    }

    public static function headerFingerprint(array $headers): string
    {
        $normalized = array_map(self::normalizeHeader(...), $headers);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private static function normalizeHeader(string $header): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($header)));
        $normalized = strtolower($ascii === false ? $header : $ascii);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized);
    }

    /** @param array<int, mixed> $row */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function assertUtf8(string $path): void
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier CSV.');
        }

        $carry = '';
        try {
            while (! feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    throw new RuntimeException('Impossible de lire le fichier CSV.');
                }
                $buffer = $carry.$chunk;
                $completeLength = feof($handle) ? strlen($buffer) : max(0, strlen($buffer) - 3);
                $complete = substr($buffer, 0, $completeLength);
                $carry = substr($buffer, $completeLength);

                if ($complete !== '' && ! mb_check_encoding($complete, 'UTF-8')) {
                    throw new InvalidArgumentException('Le fichier CSV doit être encodé en UTF-8. Enregistrez-le à nouveau dans ce format.');
                }
            }

            if ($carry !== '' && ! mb_check_encoding($carry, 'UTF-8')) {
                throw new InvalidArgumentException('Le fichier CSV doit être encodé en UTF-8. Enregistrez-le à nouveau dans ce format.');
            }
        } finally {
            fclose($handle);
        }
    }
}
