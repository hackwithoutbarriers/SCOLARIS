<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Messages de contact';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return 'Communication';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->whereNull('read_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Message reçu')->schema([
                    Forms\Components\TextInput::make('nom')->label('Nom')->readOnly(),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->readOnly(),
                    Forms\Components\TextInput::make('telephone')->label('Téléphone')->tel()->readOnly(),
                    Forms\Components\TextInput::make('sujet')->label('Sujet')->readOnly(),
                    Forms\Components\Textarea::make('message')->label('Message')->rows(8)->readOnly()->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('created_at')->label('Reçu le')->readOnly(),
                    Forms\Components\DateTimePicker::make('read_at')->label('Lu le')->readOnly(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sujet')->label('Sujet')->searchable()->limit(45),
                Tables\Columns\TextColumn::make('telephone')->label('Téléphone')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Reçu le')->dateTime()->sortable(),
                Tables\Columns\IconColumn::make('read_at')->label('Lu')->boolean()->getStateUsing(fn (ContactMessage $record): bool => $record->isRead()),
            ])
            ->filters([
                Filter::make('unread')->label('Non lus')->query(fn (Builder $query): Builder => $query->whereNull('read_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_read')
                    ->label('Marquer comme lu')
                    ->icon('heroicon-o-check')
                    ->hidden(fn (ContactMessage $record): bool => $record->isRead())
                    ->action(fn (ContactMessage $record): mixed => $record->markAsRead()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}
