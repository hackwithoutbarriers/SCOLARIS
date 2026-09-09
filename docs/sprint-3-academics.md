# Sprint 3: Academic grading

The academic domain is tenant-scoped through `BelongsToSchool` and is audited
through the existing `Auditable` concern.

## Domain

- `SubjectConfig` stores grading rules and passing thresholds per year/class.
- `Assessment` belongs to a term and subject configuration.
- `GradeCalculationService` validates scores, normalizes them to 0–100, applies
  grade bands, calculates weighted subject averages, and computes rank.
- `ReportCardService` creates normalized report-card payloads and enforces the
  draft → review → approved → published workflow. Each state is snapshotted in
  `report_card_versions`.
- `ReportCardTemplate` validates its structured JSON sections before saving.

## CSV import

`App\Services\GradeCsvImporter` accepts a CSV with the headers
`assessment_id,student_id,score`. Imports are tenant checked, validated in a
transaction, and report created/updated rows and line errors.

## PDF

`ReportCardRenderer::renderHtml()` is dependency-free. `pdf()` automatically
uses DomPDF when `barryvdh/laravel-dompdf` is installed and otherwise reports
that HTML rendering should be used.

The Filament **Academic** navigation contains subject configurations,
assessments, grades, report-card templates, and generated report cards.
