<?php
namespace App\Filament\Resources;
use App\Filament\Resources\TimetableSlotResource\Pages;
use App\Models\TimetableSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class TimetableSlotResource extends Resource {
    protected static ?string $model = TimetableSlot::class;
    protected static ?string $navigationGroup = 'Scolarité';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    public static function form(Form $form): Form { return $form->schema([
        Forms\Components\Select::make('academic_year_id')->relationship('academicYear','name')->required(),
        Forms\Components\Select::make('teacher_id')->relationship('teacher','name')->required(),
        Forms\Components\Select::make('class_room_id')->relationship('classRoom','name'),
        Forms\Components\Select::make('subject_id')->relationship('subject','name'),
        Forms\Components\Select::make('day_of_week')->options([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'])->required(),
        Forms\Components\TimePicker::make('starts_at')->required(), Forms\Components\TimePicker::make('ends_at')->required(),
        Forms\Components\TextInput::make('room')->label('Salle'), Forms\Components\Textarea::make('notes'),
    ]); }
    public static function table(Table $table): Table { return $table->columns([Tables\Columns\TextColumn::make('day_of_week'),Tables\Columns\TextColumn::make('starts_at'),Tables\Columns\TextColumn::make('ends_at'),Tables\Columns\TextColumn::make('teacher.name'),Tables\Columns\TextColumn::make('room')])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]); }
    public static function getPages(): array { return ['index'=>Pages\ListTimetableSlots::route('/'),'create'=>Pages\CreateTimetableSlot::route('/create'),'edit'=>Pages\EditTimetableSlot::route('/{record}/edit')]; }
}
