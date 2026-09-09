<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationRequestResource\Pages;
use App\Models\RegistrationRequest;
use App\Services\RegistrationApprovalService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegistrationRequestResource extends Resource
{
    protected static ?string $model = RegistrationRequest::class;
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Demandes d’accès';
    protected static ?string $modelLabel = 'Demande d’accès';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isDirector();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        return parent::getEloquentQuery()
            ->when($user && !$user->isSuperAdmin(), fn (Builder $query) => $query->where('school_id', $user->school_id))
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('school.name')->label('École'),
            Tables\Columns\TextColumn::make('requested_role')->label('Rôle')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\Action::make('approve')
                ->label('Approuver')->color('success')->requiresConfirmation()
                ->visible(fn (RegistrationRequest $record): bool => $record->status === RegistrationRequest::PENDING)
                ->action(fn (RegistrationRequest $record) => app(RegistrationApprovalService::class)->approve($record, auth()->user())),
            Tables\Actions\Action::make('reject')
                ->label('Refuser')->color('danger')->requiresConfirmation()
                ->form([ \Filament\Forms\Components\Textarea::make('reason')->required()->maxLength(1000) ])
                ->visible(fn (RegistrationRequest $record): bool => $record->status === RegistrationRequest::PENDING)
                ->action(fn (RegistrationRequest $record, array $data) => app(RegistrationApprovalService::class)->reject($record, auth()->user(), $data['reason'])),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRegistrationRequests::route('/')];
    }
}
