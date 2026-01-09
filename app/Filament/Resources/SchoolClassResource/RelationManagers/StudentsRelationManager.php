<?php

namespace App\Filament\Resources\SchoolClassResource\RelationManagers;

use App\Models\Student;
use App\Models\StudentClassHistory;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Str;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';
    protected static ?string $title = 'Siswa di Kelas';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->label('Siswa')
                ->searchable()
                ->options(function () {
                    $class = $this->ownerRecord;

                    return Student::query()
                        ->whereDoesntHave('classHistories', function ($q) use ($class) {
                            $q->where('academic_year_id', $class->academic_year_id)
                              ->where('is_active', true);
                        })
                        ->pluck('name', 'id');
                })
                ->required(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.nis')
                    ->label('NIS'),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Status')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Riwayat'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Assign Siswa')
                    ->using(function (array $data) {
                        $class = $this->ownerRecord;

                        // Nonaktifkan kelas aktif sebelumnya di tahun ajaran yang sama
                        StudentClassHistory::where('student_id', $data['student_id'])
                            ->where('academic_year_id', $class->academic_year_id)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);

                        return StudentClassHistory::create([
                            'id' => (string) Str::uuid(),
                            'student_id' => $data['student_id'],
                            'class_id' => $class->id,
                            'academic_year_id' => $class->academic_year_id,
                            'is_active' => true,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('keluarkan')
                    ->label('Keluarkan dari Kelas')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_active' => false])),
            ]);
    }
}