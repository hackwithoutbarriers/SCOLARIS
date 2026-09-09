<?php
namespace App\Filament\Resources;
use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    protected static ?string $navigationGroup = 'Academic Setup';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    public static function form(Form $form): Form { return $form->schema([Forms\Components\TextInput::make('name')->required(), Forms\Components\TextInput::make('code'), Forms\Components\Textarea::make('description')]); }
    public static function table(Table $table): Table { return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('code')])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]); }
    public static function getPages(): array { return ['index' => Pages\ListSubjects::route('/'), 'create' => Pages\CreateSubject::route('/create'), 'edit' => Pages\EditSubject::route('/{record}/edit')]; }
}
