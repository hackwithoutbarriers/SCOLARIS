<?php
namespace App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use App\Services\StudentCsvImporter;
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
                ->action(function (array $data): void {
                    app(StudentCsvImporter::class)->import(Storage::disk('local')->path($data['file']), auth()->user()->school_id);
                    Notification::make()->title('Students imported')->success()->send();
                }),
        ];
    }
}
