<?php

namespace App\Filament\Pages;

use App\Services\GradeCsvImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class GradeImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $title = 'Importer des notes CSV';

    protected static string $view = 'filament.pages.grade-import';

    public static function canAccess(): bool
    {
        return auth()->user()?->isDirector() === true;
    }

    public ?array $data = [];

    public array $result = [];

    public array $preview = [];

    public function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('file')->label('Fichier CSV')->acceptedFileTypes(['text/csv', 'text/plain'])->required()->disk('local')->directory('grade-imports'),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Prévisualiser')
                ->color('primary')
                ->action('preview'),
            Action::make('confirmImport')
                ->label('Confirmer l’import')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->preview !== [])
                ->action('import'),
        ];
    }

    public function import(GradeCsvImporter $importer): void
    {
        $state = $this->form->getState();
        $path = Storage::disk('local')->path($state['file']);
        abort_unless($this->preview !== [], 422, 'Prévisualisez le fichier avant de confirmer l’import.');
        $this->result = $importer->import($path, (int) auth()->user()->school_id);
        Notification::make()->success()->title('Import terminé')->body("{$this->result['created']} importée(s), {$this->result['errors']} erreur(s).")->send();
    }

    public function preview(GradeCsvImporter $importer): void
    {
        $state = $this->form->getState();
        $path = Storage::disk('local')->path($state['file']);
        $this->preview = $importer->preview($path);
        Notification::make()->success()->title('Prévisualisation prête')->body("{$this->preview['valid']} ligne(s) valide(s), {$this->preview['invalid']} invalide(s).")->send();
    }
}
