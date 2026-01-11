<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages;
use App\Filament\Resources\SchoolClassResource\RelationManagers\StudentsRelationManager;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\User;
use App\Models\StudentClassHistory;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Kelas';
    protected static ?string $pluralLabel = 'Kelas';

    /**
     * ✅ Semua role sah boleh lihat menu
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->isGuru()
            || $user->isKepsek()
            || $user->isBendahara();
    }

    /**
     * ✅ FINAL — Role-based data access
     * ❌ TANPA canView / authorize
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Super roles → semua kelas
        if (
            $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->isKepsek()
            || $user->isBendahara()
        ) {
            return $query;
        }

        // Guru → hanya kelas sendiri
        if ($user->isGuru()) {
            return $query->where('homeroom_teacher_id', $user->id);
        }

        // Role lain → kosong
        return $query->whereRaw('1 = 0');
    }

    /**
     * 🔒 CREATE hanya admin
     */
    public static function canCreate(): bool
    {
        return auth()->user()->isSuperAdmin()
            || auth()->user()->hasRole('admin');
    }

    /**
     * 🧊 FORM READ ONLY untuk non-admin
     */
    public static function form(Form $form): Form
    {
        $isReadOnly = fn () =>
            auth()->user()->isGuru()
            || auth()->user()->isBendahara()
            || auth()->user()->isKepsek();

        return $form->schema([
            Forms\Components\Select::make('academic_year_id')
                ->label('Tahun Ajaran')
                ->options(
                    AcademicYear::orderByDesc('is_active')
                        ->get()
                        ->mapWithKeys(fn ($year) => [
                            $year->id => $year->label,
                        ])
                )
                ->default(
                    AcademicYear::active()->value('id')
                )
                ->disabled($isReadOnly)
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
                ->disabled($isReadOnly)
                ->required(),

            Forms\Components\TextInput::make('code')
                ->label('Kode Kelas')
                ->disabled($isReadOnly)
                ->required(),

            Forms\Components\Select::make('homeroom_teacher_id')
                ->label('Wali Kelas')
                ->options(
                    User::role('guru')->pluck('fullname', 'id')
                )
                ->searchable()
                ->disabled($isReadOnly),
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

                Tables\Columns\TextColumn::make('homeroomTeacher.fullname')
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
                Tables\Actions\EditAction::make()
                    ->visible(fn () =>
                        auth()->user()->isSuperAdmin()
                        || auth()->user()->hasRole('admin')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus (Kosongkan Kelas)')
                    ->visible(fn () =>
                        auth()->user()->isSuperAdmin()
                        || auth()->user()->hasRole('admin')
                    )
                    ->before(function ($records) {
                        foreach ($records as $class) {
                            StudentClassHistory::where('class_id', $class->id)
                                ->where('is_active', true)
                                ->update(['is_active' => false]);
                        }
                    }),
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
            'index'  => Pages\ListSchoolClasses::route('/'),
            'create' => Pages\CreateSchoolClass::route('/create'),
            'edit'   => Pages\EditSchoolClass::route('/{record}/edit'),
        ];
    }
}