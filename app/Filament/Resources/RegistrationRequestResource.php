<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationRequestResource\Pages;
use App\Models\RegistrationRequest;
use App\Models\School;
use App\Services\RegistrationApprovalService;
use App\Services\StaffInvitationService;
use App\Support\Tenancy\SchoolContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
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

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user && ! $user->isSuperAdmin(), fn (Builder $query) => $query->where('school_id', $user->school_id))
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table->headerActions([
            Tables\Actions\Action::make('inviteStaff')
                ->label('Inviter un membre du personnel')
                ->visible(fn (): bool => auth()->user()?->isDirector()
                    || (auth()->user()?->isSuperAdmin()
                        && auth()->user()->hasActiveSchoolIntervention()))
                ->form([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->email()->required(fn (Get $get): bool => blank($get('phone'))),
                    TextInput::make('phone')
                        ->tel()
                        ->required(fn (Get $get): bool => blank($get('email'))),
                    Select::make('role')->label('Rôle principal')->options([
                        'teacher' => 'Enseignant',
                        'accountant' => 'Comptable',
                        'secretary' => 'Secrétaire',
                        'censeur' => 'Censeur',
                    ])->required(),
                    Select::make('secondary_role')->label('Rôle secondaire (optionnel)')->options([
                        'teacher' => 'Enseignant',
                        'accountant' => 'Comptable',
                        'secretary' => 'Secrétaire',
                        'censeur' => 'Censeur',
                    ]),
                    Select::make('school_id')
                        ->label('École')
                        ->options(function (): array {
                            $schoolId = app(SchoolContext::class)->id();

                            return $schoolId
                                ? School::query()->whereKey($schoolId)->pluck('name', 'id')->all()
                                : [];
                        })
                        ->default(fn (): ?int => app(SchoolContext::class)->id())
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin())
                        ->disabled()
                        ->required(fn (): bool => auth()->user()?->isSuperAdmin()),
                ])
                ->action(function (array $data): void {
                    [$invitation, $token] = app(StaffInvitationService::class)
                        ->create(auth()->user(), $data);

                    Notification::make()
                        ->title('Invitation mise en file')
                        ->body('Elle sera envoyée par le canal de notification configuré.')
                        ->persistent()
                        ->success()
                        ->send();
                }),
        ])->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('school.name')->label('École'),
            Tables\Columns\TextColumn::make('requested_role')->label('Rôle')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\Action::make('approve')
                ->label('Approuver')->color('success')->requiresConfirmation()
                ->visible(fn (RegistrationRequest $record): bool => $record->status === RegistrationRequest::PENDING
                    && (auth()->user()?->isDirector()
                        || (auth()->user()?->isSuperAdmin()
                            && (($record->requested_role === 'director'
                                && $record->request_type === 'new_school'
                                && ! $record->school_id)
                                || auth()->user()->hasActiveSchoolIntervention((int) $record->school_id)))))
                ->action(fn (RegistrationRequest $record) => app(RegistrationApprovalService::class)->approve($record, auth()->user())),
            Tables\Actions\Action::make('reject')
                ->label('Refuser')->color('danger')->requiresConfirmation()
                ->form([Textarea::make('reason')->required()->maxLength(1000)])
                ->visible(fn (RegistrationRequest $record): bool => $record->status === RegistrationRequest::PENDING)
                ->action(fn (RegistrationRequest $record, array $data) => app(RegistrationApprovalService::class)->reject($record, auth()->user(), $data['reason'])),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRegistrationRequests::route('/')];
    }
}
