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
            return Certificate::create(['school_id'=>$student->school_id,'student_id'=>$student->id,'template_id'=>$template?->id,'document_type'=>$type,'number'=>$data['number'],'data'=>$data,'generated_by'=>auth()->id()]);
        });
    }
    public function html(Certificate $certificate): string { return $this->renderer->renderDocument($certificate->data ?? [], $certificate->template?->schema ?? ReportCardTemplate::defaultSchema(), $certificate->number); }
    public function pdf(Certificate $certificate): mixed { return $this->renderer->pdfHtml($this->html($certificate)); }
}
