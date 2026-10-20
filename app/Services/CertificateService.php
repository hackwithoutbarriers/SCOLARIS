<?php
namespace App\Services;
use App\Models\Certificate;
use App\Models\ReportCardTemplate;
use App\Models\ReceiptSequence;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class CertificateService {
    public function __construct(private readonly ReportCardRenderer $renderer) {}
    public function generate(Student $student, string $documentType = 'certificate', ?ReportCardTemplate $template = null, array $attributes = []): Certificate {
        if ($template && $template->school_id !== $student->school_id) throw ValidationException::withMessages(['school'=>'Le modèle doit appartenir à la même école.']);
        $type = strtolower($documentType);
        $prefix = $type === 'attestation' ? 'ATT' : 'CERT';
        return DB::transaction(function () use ($student,$template,$attributes,$type,$prefix): Certificate {
            ReceiptSequence::query()->insertOrIgnore(['school_id'=>$student->school_id,'sequence_type'=>$type,'next_number'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $sequence = ReceiptSequence::query()->where('school_id',$student->school_id)->where('sequence_type',$type)->lockForUpdate()->firstOrFail();
            $number = (int) $sequence->next_number;
            $sequence->increment('next_number');
            $student->loadMissing('school');
            $data = array_merge(['document_type'=>$type,'number'=>$prefix.'-'.str_pad((string)$number,6,'0',STR_PAD_LEFT),'school_identity'=>['name'=>$student->school?->name,'address'=>$student->school?->address,'phone'=>$student->school?->phone,'logo_path'=>$student->school?->logo_path],'student_identity'=>['name'=>$student->full_name ?? trim($student->first_name.' '.$student->last_name),'student_number'=>$student->student_number]], $attributes);
            return Certificate::create(['school_id'=>$student->school_id,'student_id'=>$student->id,'template_id'=>$template?->id,'document_type'=>$type,'number'=>$data['number'],'data'=>$data,'generated_by'=>auth()->id(),'status'=>'validated','validated_by'=>auth()->id(),'validated_at'=>now()]);
        });
    }

    public function prepare(array $data): Certificate
    {
        $student = Student::query()->findOrFail($data['student_id']);
        abort_unless(auth()->user()?->isSecretary() || auth()->user()?->isDirector(), 403);
        abort_unless((int) $student->school_id === (int) auth()->user()->school_id, 403);
        return Certificate::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'document_type' => $data['document_type'],
            'data' => ['workflow_note' => $data['workflow_note'] ?? null],
            'status' => auth()->user()->isDirector() ? 'pending_validation' : 'draft',
            'requested_by' => auth()->id(),
            'workflow_note' => $data['workflow_note'] ?? null,
        ]);
    }

    public function submit(Certificate $certificate): Certificate
    {
        abort_unless(auth()->user()?->isSecretary() && $certificate->school_id === auth()->user()->school_id, 403);
        abort_unless($certificate->status === 'draft', 422);
        $certificate->update(['status' => 'pending_validation']);
        return $certificate->fresh();
    }

    public function validate(Certificate $certificate): Certificate
    {
        abort_unless(auth()->user()?->isDirector() && $certificate->school_id === auth()->user()->school_id, 403);
        abort_unless(in_array($certificate->status, ['pending_validation', 'draft'], true), 422);
        return DB::transaction(function () use ($certificate): Certificate {
            $type = strtolower($certificate->document_type);
            $prefix = $type === 'attestation' ? 'ATT' : 'CERT';
            ReceiptSequence::query()->insertOrIgnore(['school_id' => $certificate->school_id, 'sequence_type' => $type, 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = ReceiptSequence::query()->where('school_id', $certificate->school_id)->where('sequence_type', $type)->lockForUpdate()->firstOrFail();
            $number = $prefix.'-'.str_pad((string) $sequence->next_number, 6, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');
            $data = array_merge($certificate->data ?? [], ['document_type' => $type, 'number' => $number]);
            $certificate->update(['number' => $number, 'data' => $data, 'status' => 'validated', 'validated_by' => auth()->id(), 'validated_at' => now(), 'generated_by' => auth()->id()]);
            return $certificate->fresh();
        });
    }

    public function deliver(Certificate $certificate, string $method, string $recipient): Certificate
    {
        abort_unless((auth()->user()?->isSecretary() || auth()->user()?->isDirector()) && $certificate->school_id === auth()->user()->school_id, 403);
        abort_unless($certificate->status === 'validated', 422);
        abort_unless(in_array($method, ['school', 'whatsapp', 'email'], true), 422);
        $certificate->update(['status' => 'delivered', 'delivery_method' => $method, 'delivered_to' => $recipient, 'delivered_by' => auth()->id(), 'delivered_at' => now()]);
        return $certificate->fresh();
    }

    public function cancel(Certificate $certificate, string $reason): Certificate
    {
        abort_unless(auth()->user()?->isDirector() && $certificate->school_id === auth()->user()->school_id, 403);
        abort_unless(in_array($certificate->status, ['draft', 'pending_validation', 'validated'], true), 422);
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Le motif d’annulation doit comporter au moins 10 caractères.']);
        }
        $certificate->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
        return $certificate->fresh();
    }
    public function html(Certificate $certificate): string { return $this->renderer->renderDocument($certificate->data ?? [], $certificate->template?->schema ?? ReportCardTemplate::defaultSchema(), $certificate->number); }
    public function pdf(Certificate $certificate): mixed { return $this->renderer->pdfHtml($this->html($certificate)); }
}
