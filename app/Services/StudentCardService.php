<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Student;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;

final class StudentCardService
{
    public function token(Student $student): string
    {
        if (!$student->card_token) {
            $student->forceFill(['card_token' => Str::random(48)])->saveQuietly();
        }
        return $student->card_token;
    }

    public function qrDataUri(Student $student): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $this->token($student),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 180,
            margin: 5,
        ))->build();

        return $result->getDataUri();
    }

    public function cardsForClass(ClassRoom $classRoom): \Illuminate\Support\Collection
    {
        return Student::query()->where('school_id', $classRoom->school_id)
            ->whereHas('enrollments', fn ($query) => $query->where('class_room_id', $classRoom->id)->where('status', 'active'))
            ->with(['enrollments' => fn ($query) => $query->where('class_room_id', $classRoom->id)->latest('id'), 'school'])
            ->orderBy('last_name')->orderBy('first_name')->get();
    }
}
