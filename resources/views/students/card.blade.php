<!doctype html>
<html lang="fr"><meta charset="utf-8">
<style>
@page{margin:10mm}body{font-family:DejaVu Sans;font-size:10px;color:#0f172a}
.card{border:1px solid #94a3b8;border-radius:5px;padding:10px;margin-bottom:12px;height:112mm;position:relative}
h1{font-size:16px;color:#1e3a8a;margin:0 0 5px}.school{font-size:12px;font-weight:bold}
.photo{width:70px;height:85px;border:1px solid #cbd5e1;object-fit:cover;float:left;margin:10px 12px 8px 0}
.qr{position:absolute;right:12px;bottom:10px;width:105px;height:105px}.line{margin:7px 0}
</style>
@foreach($students as $student)
@php($enrollment = $student->enrollments->sortByDesc('id')->first())
<section class="card">
  <div class="school">{{ $student->school?->name }}</div>
  <h1>CARTE SCOLAIRE</h1>
  @if($student->photo_path)<img class="photo" src="{{ public_path($student->photo_path) }}">@endif
  <div class="line"><strong>Élève :</strong> {{ $student->full_name }}</div>
  <div class="line"><strong>Matricule :</strong> {{ $student->student_number }}</div>
  <div class="line"><strong>Classe :</strong> {{ $enrollment?->classRoom?->name }}</div>
  <div class="line"><strong>Né(e) le :</strong> {{ $student->date_of_birth?->format('d/m/Y') }}</div>
  <img class="qr" src="{{ $cards->qrDataUri($student) }}" alt="QR">
</section>
@endforeach
</html>
