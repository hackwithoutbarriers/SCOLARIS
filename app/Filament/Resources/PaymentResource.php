<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->options(fn () => Student::query()->orderBy('last_name')->get()->pluck('full_name', 'id'))->searchable()->required(),
            Forms\Components\TextInput::make('amount')->label('Montant')->numeric()->integer()->minValue(1)->required(),
            Forms\Components\Select::make('payment_method')->label('Mode de paiement')->options(['CASH' => 'Espèces', 'BANK' => 'Banque', 'TMONEY' => 'T-Money', 'FLOOZ' => 'Flooz', 'OTHER' => 'Autre'])->required(),
            Forms\Components\TextInput::make('reference')->maxLength(120),
            Forms\Components\Textarea::make('notes'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.full_name')->label('Élève')->searchable()->weight('bold')->description(fn (Payment $record): string => $record->payment_method.' · '.($record->reference ?: '—'))->wrap(),
            Tables\Columns\TextColumn::make('amount')->money('XOF')->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('payment_method')->badge()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => $state === Payment::CONFIRMED ? 'success' : 'warning'),
            Tables\Columns\TextColumn::make('paid_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([Tables\Actions\Action::make('receipt')->url(fn (Payment $record): string => route('payments.receipt', $record))->openUrlInNewTab()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayments::route('/'), 'create' => Pages\CreatePayment::route('/create')];
    }
}
