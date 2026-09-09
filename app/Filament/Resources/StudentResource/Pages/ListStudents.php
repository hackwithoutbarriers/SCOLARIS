<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Services\StudentCsvImporter;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([Forms\Components\FileUpload::make('file')->required()->acceptedFileTypes(['text/csv', 'text/plain'])->disk('local')])
                ->visible(fn (): bool => auth()->user()?->isDirector() === true)
                ->action(function (array $data): void {
                    abort_unless(auth()->user()?->isDirector() === true, 403);
                    app(StudentCsvImporter::class)->import(Storage::disk('local')->path($data['file']), auth()->user()->school_id);
                    Notification::make()->title('Students imported')->success()->send();
                }),
        ];
    }
}
