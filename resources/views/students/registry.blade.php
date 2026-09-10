<!doctype html>
<html lang="fr"><meta charset="utf-8"><style>body{font-family:DejaVu Sans;font-size:10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #999;padding:4px;text-align:left}h1{font-size:16px}</style>
<h1>Registre matricule</h1>
<table><thead><tr><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Naissance</th><th>Sexe</th><th>Inscription</th><th>Classe</th></tr></thead><tbody>
@foreach($students as $student)<tr>@php($enrollment = $student->enrollments->sortByDesc('id')->first())<td>{{ $student->student_number }}</td><td>{{ $student->last_name }}</td><td>{{ $student->first_name }}</td><td>{{ $student->date_of_birth?->format('d/m/Y') }}</td><td>{{ $student->gender }}</td><td>{{ $enrollment?->enrolled_at?->format('d/m/Y') }}</td><td>{{ $enrollment?->classRoom?->name }}</td></tr>@endforeach
</tbody></table>
