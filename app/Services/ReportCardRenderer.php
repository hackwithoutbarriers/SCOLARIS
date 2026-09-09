<?php

namespace App\Services;

use App\Models\ReportCard;
use App\Models\ReportCardTemplate;
use RuntimeException;

final class ReportCardRenderer
{
    public function renderHtml(ReportCard $card): string
    {
        $data = $card->normalizedData()?->toArray() ?? [];
        $schema = $card->template?->schema ?? ReportCardTemplate::defaultSchema();
        $styles = $schema['styles'] ?? [];
        $orientation = $card->template?->orientation ?? ($schema['page']['orientation'] ?? 'portrait');
        $columns = $schema['subjects']['columns'] ?? ['subject', 'average', 'grade_letter'];
        $labels = ['subject' => 'Matière', 'average' => 'Moyenne', 'grade_letter' => 'Appréciation', 'weight' => 'Coef.', 'passing_score' => 'Seuil'];
        $school = $data['school_identity'] ?? [];
        $student = $data['student_identity'] ?? $data['student'] ?? [];
        $rows = '';
        foreach ($data['subjects'] ?? [] as $subject) {
            $rows .= '<tr>'.implode('', array_map(fn (string $column): string => '<td>'.e((string) ($subject[$column] ?? '')).'</td>', $columns)).'</tr>';
        }
        $head = implode('', array_map(fn (string $column): string => '<th>'.e($labels[$column] ?? ucfirst(str_replace('_', ' ', $column))).'</th>', $columns));
        $logo = !empty($school['logo_path']) ? '<img class="logo" src="'.e(public_path($school['logo_path'])).'" alt="Logo">' : '';
        $primary = e($styles['primary_color'] ?? '#1E3A8A');
        $font = e($styles['font_family'] ?? 'Roboto');
        $title = e($styles['title'] ?? 'Bulletin scolaire');
        $customHeader = e((string) ($schema['header']['custom_text'] ?? ''));
        $customFooter = e((string) ($schema['footer']['custom_text'] ?? ''));
        $showAttendance = (bool) ($schema['summary']['show_attendance'] ?? $schema['summary']['absences'] ?? false);
        $attendance = $data['attendance_summary'] ?? $data['attendance'] ?? [];
        return '<!doctype html><html><head><meta charset="utf-8"><title>'.$title.'</title><style>'
            .'@page{size:'.e($orientation).';margin:'.(int) ($schema['page']['margins']['top'] ?? 12).'mm '.(int) ($schema['page']['margins']['right'] ?? 12).'mm '.(int) ($schema['page']['margins']['bottom'] ?? 12).'mm '.(int) ($schema['page']['margins']['left'] ?? 12).'mm}'
            .'body{font-family:'.$font.',sans-serif;color:#0F172A;font-size:'.(int) ($styles['font_size'] ?? 10).'pt}h1{color:'.$primary.';text-align:center}.logo{max-height:70px;max-width:160px}table{width:100%;border-collapse:collapse;margin-top:18px}th{background:'.$primary.';color:#fff}th,td{border:1px solid #cbd5e1;padding:6px;text-align:left}.meta{margin:8px 0 16px}.footer{margin-top:24px}</style></head><body>'
            .(($schema['header']['enabled'] ?? true) ? '<header>'.$logo.'<h1>'.$title.'</h1><div>'.e($school['name'] ?? '').' '.e($school['address'] ?? '').' '.e($school['phone'] ?? '').'</div><div>'.$customHeader.'</div></header>' : '')
            .'<section class="meta"><strong>Élève :</strong> '.e($student['name'] ?? '').' &nbsp; <strong>N° :</strong> '.e($student['student_number'] ?? '').'<br><strong>Période :</strong> '.e($data['period']['term'] ?? '').' &nbsp; <strong>Classe :</strong> '.e($data['class']['name'] ?? '').'</section>'
            .'<table><thead><tr>'.$head.'</tr></thead><tbody>'.$rows.'</tbody></table>'
            .'<p><strong>Moyenne générale :</strong> '.e(number_format((float) ($data['summary']['average'] ?? 0), 2)).' %</p>'
            .($showAttendance ? '<p><strong>Présences :</strong> '.e((string) ($attendance['days_present'] ?? 0)).' | <strong>Absences :</strong> '.e((string) ($attendance['days_absent'] ?? 0)).' | <strong>Retards :</strong> '.e((string) ($attendance['days_late'] ?? 0)).' | <strong>Excusés :</strong> '.e((string) ($attendance['days_excused'] ?? 0)).' | <strong>Total :</strong> '.e((string) ($attendance['total_days'] ?? 0)).'</p>' : '')
            .'<p><strong>Appréciation :</strong> '.e($data['appreciation']['label'] ?? '').'</p><footer class="footer">'.$customFooter.'</footer></body></html>';
    }

    public function pdf(ReportCard $card): mixed
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new RuntimeException('PDF generation requires barryvdh/laravel-dompdf. HTML rendering is available.');
        }
        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->renderHtml($card));
    }
}
