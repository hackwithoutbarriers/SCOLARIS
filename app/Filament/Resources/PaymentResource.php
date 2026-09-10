<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Payments\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';
    protected static ?string $pluralModelLabel = 'Paiements';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isFinanceOperator() === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->label('Élève')->options(fn () => Student::query()->orderBy('last_name')->get()->pluck('full_name', 'id'))->searchable()->required(),
            Forms\Components\TextInput::make('amount')->label('Montant')->numeric()->integer()->minValue(1)->required(),
            Forms\Components\Select::make('payment_method')->label('Mode de paiement')->options(['CASH' => 'Espèces'])->default('CASH')->required(),
            Forms\Components\TextInput::make('reference')->maxLength(120),
            Forms\Components\Textarea::make('notes'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.full_name')->label('Élève')->searchable()->weight('bold')->description(fn (Payment $record): string => ($record->payment_method === 'CASH' ? 'Paiement en espèces' : $record->payment_method).' · '.($record->reference ?: '—'))->wrap(),
            Tables\Columns\TextColumn::make('amount')->money('XOF')->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('payment_method')->badge()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('status')->label('Statut')->badge()->icon(fn (string $state): string => $state === Payment::CONFIRMED ? 'heroicon-o-check-circle' : 'heroicon-o-clock')->formatStateUsing(fn (string $state): string => match ($state) {
                Payment::CONFIRMED => 'Confirmé',
                Payment::PENDING => 'En attente',
                Payment::FAILED => 'Échec',
                Payment::CANCELLED => 'Annulé',
                Payment::REFUNDED => 'Remboursé',
                default => $state,
            })->color(fn (string $state): string => $state === Payment::CONFIRMED ? 'success' : 'warning'),
            Tables\Columns\TextColumn::make('paid_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([
            Tables\Actions\Action::make('reverse')
                ->label('Corriger le paiement')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reason')->label('Motif')->required()->minLength(10),
                ])
                ->visible(fn (Payment $record): bool => auth()->user()?->can('reverse', $record) === true)
                ->disabled(fn (Payment $record): bool => (int) $record->reversals()->sum('amount') >= (int) $record->amount)
                ->action(function (Payment $record, array $data): void {
                    app(PaymentService::class)->reverse($record, (int) $record->amount, $data['reason']);
                }),
            Tables\Actions\Action::make('history')
                ->label('Historique des corrections')
                ->icon('heroicon-o-clock')
                ->modalHeading('Historique des corrections')
                ->modalContent(fn (Payment $record) => view('filament.payments.reversal-history', ['reversals' => $record->reversals()->with('creator')->latest()->get()])),
            Tables\Actions\Action::make('receipt')->label('Reçu')->url(fn (Payment $record): string => route('payments.receipt', $record))->openUrlInNewTab(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayments::route('/'), 'create' => Pages\CreatePayment::route('/create')];
    }
}
