<?php
namespace App\Filament\Resources;
use App\Filament\Resources\TermResource\Pages;
use App\Models\Term;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class TermResource extends Resource
{
    protected static ?string $model = Term::class;
    protected static ?string $navigationGroup = 'Academic Setup';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    public static function form(Form $form): Form { return $form->schema([
        Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
        Forms\Components\TextInput::make('name')->required(),
        Forms\Components\DatePicker::make('starts_at')->required(),
        Forms\Components\DatePicker::make('ends_at')->required(),
        Forms\Components\TextInput::make('sort_order')->numeric()->default(1)->required(),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('name'), Tables\Columns\TextColumn::make('academicYear.name')->label('Academic year'), Tables\Columns\TextColumn::make('starts_at')->date(), Tables\Columns\TextColumn::make('ends_at')->date(),
    ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]); }
    public static function getPages(): array { return ['index' => Pages\ListTerms::route('/'), 'create' => Pages\CreateTerm::route('/create'), 'edit' => Pages\EditTerm::route('/{record}/edit')]; }
}
