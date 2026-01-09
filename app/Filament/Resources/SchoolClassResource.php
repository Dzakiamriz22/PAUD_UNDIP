<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Filament\Resources\SchoolClassResource\RelationManagers\StudentsRelationManager;


class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Kelas';
    protected static ?string $pluralLabel = 'Kelas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')
                ->label('Tahun Ajaran')
                ->options(
                    AcademicYear::query()
                        ->orderByDesc('is_active')
                        ->get()
                        ->pluck('label', 'id')
                )
                ->default(AcademicYear::active()->value('id'))
                ->required(),



            Forms\Components\Select::make('category')
                ->label('Kategori')
                ->options([
                    'TK' => 'TK',
                    'KB' => 'KB',
                    'TPA_PAUD' => 'TPA PAUD',
                    'TPA_SD' => 'TPA SD',
                    'TPA_TK' => 'TPA TK',
                    'TPA_KB' => 'TPA KB',
                ])
                ->required(),

            Forms\Components\TextInput::make('code')
                ->label('Kode Kelas')
                ->placeholder('A1, B1, KB1')
                ->required(),

            Forms\Components\Select::make('homeroom_teacher_id')
                ->label('Wali Kelas')
                ->options(
                    User::query()
                        ->pluck('fullname', 'id')
                )
                ->searchable()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.label')
                    ->label('Tahun Ajaran')
                    ->sortable(),


                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategori'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable(),

                Tables\Columns\TextColumn::make('homeroomTeacher.name')
                    ->label('Wali Kelas')
                    ->default('-'),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Jumlah Siswa')
                    ->counts('students'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->options(
                        AcademicYear::pluck('year', 'id')
                    ),

                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'TK' => 'TK',
                        'KB' => 'KB',
                        'TPA_PAUD' => 'TPA PAUD',
                        'TPA_SD' => 'TPA SD',
                        'TPA_TK' => 'TPA TK',
                        'TPA_KB' => 'TPA KB',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchoolClasses::route('/'),
            'create' => Pages\CreateSchoolClass::route('/create'),
            'edit' => Pages\EditSchoolClass::route('/{record}/edit'),
        ];
    }
}