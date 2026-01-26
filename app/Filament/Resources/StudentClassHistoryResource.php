<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentClassHistoryResource\Pages;
use App\Models\StudentClassHistory;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StudentClassHistoryResource extends Resource
{
    protected static ?string $model = StudentClassHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Riwayat Kelas';
    protected static ?string $pluralLabel = 'Riwayat Kelas';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.nis')
                    ->label('NIS'),

                Tables\Columns\TextColumn::make('classRoom.code')
                    ->label('Kelas')
                    ->badge(),

                Tables\Columns\TextColumn::make('academicYear.label')
                    ->label('Tahun Ajaran'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->options(
                        \App\Models\AcademicYear::query()
                            ->orderByDesc('is_active')
                            ->get()
                            ->mapWithKeys(fn ($ay) => [
                                $ay->id => "{$ay->year} – " . ucfirst($ay->semester),
                            ])
                    ),


                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('classRoom', 'code'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentClassHistories::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_student::class::history') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_student::class::history') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view_student::class::history') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete_student::class::history') ?? false;
    }

    public static function canUpdate(Model $record): bool
    {
        return auth()->user()?->can('update_student::class::history') ?? false;
    }
}