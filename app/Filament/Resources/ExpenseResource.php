<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Services\ExpenseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';
    protected static ?string $navigationGroup = 'Finances';
    protected static ?string $navigationLabel = 'Dépenses';
    protected static ?string $modelLabel = 'Dépense';
    protected static ?string $pluralModelLabel = 'Dépenses';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isFinanceOperator() === true;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('accountant') === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category')->options(fn (): array => auth()->user()?->school?->expense_categories ?: ExpenseService::DEFAULT_CATEGORIES)->required(),
            Forms\Components\TextInput::make('amount')->numeric()->integer()->minValue(1)->required(),
            Forms\Components\DatePicker::make('expense_date')->default(today())->required(),
            Forms\Components\Textarea::make('description')->label('Description')->required(),
            Forms\Components\FileUpload::make('justification')->label('Pièce justificative')->acceptedFileTypes(['image/*', 'application/pdf'])->disk('public'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('voucher_number')->label('Bon')->sortable(),
            Tables\Columns\TextColumn::make('expense_date')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('category')->badge(),
            Tables\Columns\TextColumn::make('amount')->money('XOF'),
            Tables\Columns\TextColumn::make('approval_status')->label('Validation')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                'pending' => 'En attente',
                'approved' => 'Approuvée',
                'rejected' => 'Rejetée',
                default => $state,
            }),
            Tables\Columns\TextColumn::make('recorder.name')->label('Enregistré par'),
        ])->actions([
            Tables\Actions\Action::make('approve')
                ->label('Valider')
                ->requiresConfirmation()
                ->visible(fn (Expense $record): bool => auth()->user()?->can('approve', $record) === true)
                ->action(fn (Expense $record) => app(ExpenseService::class)->approve($record)),
            Tables\Actions\Action::make('reject')
                ->label('Rejeter')
                ->color('danger')
                ->visible(fn (Expense $record): bool => auth()->user()?->can('reject', $record) === true)
                ->form([Forms\Components\Textarea::make('reason')->label('Motif du rejet')->required()->minLength(10)])
                ->action(fn (Expense $record, array $data) => app(ExpenseService::class)->reject($record, $data['reason'])),
            Tables\Actions\Action::make('resubmit')
                ->label('Corriger et resoumettre')
                ->visible(fn (Expense $record): bool => auth()->user()?->can('resubmit', $record) === true)
                ->form([
                    Forms\Components\Select::make('category')->options(fn (): array => auth()->user()?->school?->expense_categories ?: ExpenseService::DEFAULT_CATEGORIES)->required(),
                    Forms\Components\TextInput::make('amount')->numeric()->integer()->minValue(1)->required(),
                    Forms\Components\DatePicker::make('expense_date')->required(),
                    Forms\Components\Textarea::make('description')->label('Description')->required(),
                    Forms\Components\FileUpload::make('justification')->label('Pièce justificative')->acceptedFileTypes(['image/*', 'application/pdf'])->disk('public'),
                ])
                ->action(fn (Expense $record, array $data) => app(ExpenseService::class)->resubmit($record, $data)),
            Tables\Actions\Action::make('reverse')->label('Corriger')->visible(fn (Expense $record): bool => auth()->user()?->isDirector() === true)
                ->form([Forms\Components\TextInput::make('amount')->numeric()->required(), Forms\Components\Textarea::make('reason')->required()->minLength(10)])
                ->action(fn (Expense $record, array $data) => app(ExpenseService::class)->reverse($record, (int) $data['amount'], $data['reason'])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListExpenses::route('/'), 'create' => Pages\CreateExpense::route('/create')];
    }
}
