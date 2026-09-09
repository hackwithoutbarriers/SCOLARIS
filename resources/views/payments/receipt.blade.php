<!doctype html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;color:#0F172A}h1{color:#1E3A8A}.total{font-size:20px;font-weight:bold}.muted{color:#64748B}</style></head>
<body>
<h1>{{ $payment->student->school->name }}</h1>
<p class="muted">Reçu {{ $payment->receipt->number }} · {{ $payment->paid_at?->format('d/m/Y H:i') }}</p>
<p>Élève : <strong>{{ $payment->student->full_name }}</strong></p>
<p>Montant : <span class="total">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</span></p>
<p>Méthode : {{ $payment->payment_method }} · Référence : {{ $payment->reference ?: '—' }}</p>
<p>Reste à payer : <strong>{{ number_format($payment->receipt->balance_after, 0, ',', ' ') }} {{ $payment->currency }}</strong></p>
<p class="muted">Enregistré par : {{ $payment->receiver?->name ?: 'Système' }}</p>
</body>
</html>
