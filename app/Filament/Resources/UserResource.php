<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationLabel = 'Demandes d’inscription';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_superuser;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('is_superuser', false))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'warning' => User::STATUS_PENDING,
                        'success' => User::STATUS_APPROVED,
                        'danger' => User::STATUS_REJECTED,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::STATUS_PENDING => 'En attente',
                        User::STATUS_APPROVED => 'Validé',
                        User::STATUS_REJECTED => 'Refusé',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        User::STATUS_PENDING => 'En attente',
                        User::STATUS_APPROVED => 'Validé',
                        User::STATUS_REJECTED => 'Refusé',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => (bool) auth()->user()?->is_superuser && $record->status !== User::STATUS_APPROVED)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'status' => User::STATUS_APPROVED,
                            'approved_at' => now(),
                            'approved_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Compte validé')
                            ->body("Le compte {$record->email} a été approuvé.")
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => (bool) auth()->user()?->is_superuser && $record->status !== User::STATUS_REJECTED)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'status' => User::STATUS_REJECTED,
                            'approved_at' => null,
                            'approved_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Compte refusé')
                            ->body("Le compte {$record->email} a été refusé.")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
