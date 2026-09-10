<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolSubscriptionResource\Pages;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolSubscriptionResource extends Resource
{
    protected static ?string $model = SchoolSubscription::class;

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Abonnements SaaS';

    protected static ?string $modelLabel = 'Abonnement';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')->relationship('school', 'name')->searchable()->required(),
            Forms\Components\Select::make('subscription_plan_id')->label('Plan')->options(fn (): array => SubscriptionPlan::query()->where('active', true)->pluck('name', 'id')->all())->required(),
            Forms\Components\Select::make('status')->options(['trial' => 'Essai', 'active' => 'Actif', 'past_due' => 'En retard', 'cancelled' => 'Annulé', 'expired' => 'Expiré'])->required(),
            Forms\Components\DatePicker::make('starts_at')->label('Début')->required(),
            Forms\Components\DatePicker::make('ends_at')->label('Fin'),
            Forms\Components\DatePicker::make('trial_ends_at')->label('Fin de l’essai'),
            Forms\Components\Textarea::make('notes')->label('Notes'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('school.name')->label('École')->searchable(),
            Tables\Columns\TextColumn::make('plan.name')->label('Plan'),
            Tables\Columns\TextColumn::make('status')->label('État')->badge(),
            Tables\Columns\TextColumn::make('starts_at')->label('Début')->date(),
            Tables\Columns\TextColumn::make('ends_at')->label('Fin')->date(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchoolSubscriptions::route('/'),
            'create' => Pages\CreateSchoolSubscription::route('/create'),
            'edit' => Pages\EditSchoolSubscription::route('/{record}/edit'),
        ];
    }
}
