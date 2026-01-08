<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\StudentClassHistory;
use Filament\Resources\RelationManagers\RelationManager;

class ClassHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'classHistories';

    protected static ?string $title = 'Riwayat Kelas';

    protected static ?string $recordTitleAttribute = 'class_id';

    /* ================= FORM ================= */

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_id')
                    ->label('Kelas')
                    ->options(function () {
                        $academicYear = AcademicYear::where('is_active', true)->first();

                        return SchoolClass::query()
                            ->where('academic_year_id', $academicYear?->id)
                            ->pluck('code', 'id');
                    })
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    /* ================= TABLE ================= */

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Tahun Ajaran'),

                Tables\Columns\TextColumn::make('academicYear.semester')
                    ->label('Semester'),

                Tables\Columns\TextColumn::make('classRoom.code')
                    ->label('Kelas')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Pindah / Assign Kelas')
                    ->before(function (array $data) {
                        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

                        // ❌ Cegah assign ke kelas yang sama
                        $exists = StudentClassHistory::query()
                            ->where('student_id', $this->ownerRecord->id)
                            ->where('class_id', $data['class_id'])
                            ->where('academic_year_id', $academicYear->id)
                            ->exists();

                        if ($exists) {
                            throw new \Exception('Siswa sudah berada di kelas ini.');
                        }
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

                        // Nonaktifkan histori lama
                        StudentClassHistory::where('student_id', $this->ownerRecord->id)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);

                        return [
                            'id' => (string) Str::uuid(),
                            'student_id' => $this->ownerRecord->id,
                            'class_id' => $data['class_id'],
                            'academic_year_id' => $academicYear->id,
                            'is_active' => true,
                        ];
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
