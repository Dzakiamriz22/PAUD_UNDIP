<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers\ClassHistoriesRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\PaymentHistoryRelationManager;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\StudentClassHistory;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Siswa';
    protected static ?string $pluralLabel = 'Siswa';

    /* =====================================================
     | QUERY AKSES DATA
     ===================================================== */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (
            $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->isKepsek()
            || $user->isBendahara()
            || $user->hasRole('auditor')
            || $user->hasRole('operator')
        ) {
            return $query;
        }

        if ($user->isGuru()) {
            return $query->whereHas('classHistories', function ($q) use ($user) {
                $q->where('is_active', true)
                    ->whereHas('classRoom', function ($c) use ($user) {
                        $c->where('homeroom_teacher_id', $user->id);
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /* =====================================================
     | FORM
     ===================================================== */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Siswa')
                ->schema([
                    TextInput::make('nis')
                        ->label('NIS')
                        ->required()
                        ->unique(ignoreRecord: true),

                    TextInput::make('name')
                        ->label('Nama Siswa')
                        ->required()
                        ->maxLength(255),

                    Select::make('gender')
                        ->label('Jenis Kelamin')
                        ->options([
                            'male' => 'Laki-laki',
                            'female' => 'Perempuan',
                        ])
                        ->required(),

                    DatePicker::make('birth_date')
                        ->label('Tanggal Lahir')
                        ->required(),

                    TextInput::make('parent_name')
                        ->label('Nama Orang Tua')
                        ->required(),

                    TextInput::make('parent_contact')
                        ->label('Kontak Orang Tua')
                        ->tel(),

                    Select::make('status')
                        ->options([
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            'graduated' => 'Lulus',
                        ])
                        ->default('active')
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    /* =====================================================
     | TABLE
     ===================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')->label('NIS')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),

                TextColumn::make('activeClass.classRoom.code')
                    ->label('Kelas Aktif')
                    ->badge()
                    ->color('success'),

                BadgeColumn::make('gender')
                    ->label('JK')
                    ->colors([
                        'primary' => 'male',
                        'danger' => 'female',
                    ])
                    ->formatStateUsing(fn ($state) =>
                        $state === 'male' ? 'Laki-laki' : 'Perempuan'
                    ),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'gray' => 'graduated',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'graduated' => 'Lulus',
                        default => $state,
                    }),
            ])

            /* ================= ACTIONS ================= */
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->can('update_student')),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_student')),

                Tables\Actions\Action::make('assign_or_move_class')
                    ->label(fn (Student $record) =>
                        $record->activeClass ? 'Move Class' : 'Assign Kelas'
                    )
                    ->icon(fn (Student $record) =>
                        $record->activeClass
                            ? 'heroicon-o-arrow-right-circle'
                            : 'heroicon-o-academic-cap'
                    )
                    ->color(fn (Student $record) =>
                        $record->activeClass ? 'warning' : 'success'
                    )
                    ->visible(fn () => auth()->user()->can('update_student'))
                    ->form([
                        Select::make('class_id')
                            ->label('Pilih Kelas')
                            ->options(fn () =>
                                SchoolClass::with('academicYear')
                                    ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                                    ->get()
                                    ->mapWithKeys(fn ($class) => [
                                        $class->id => "{$class->code} ({$class->academicYear->label})"
                                    ])
                            )
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Student $record, array $data) {

                        // Nonaktifkan kelas lama (jika ada)
                        StudentClassHistory::where('student_id', $record->id)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);

                        $class = SchoolClass::findOrFail($data['class_id']);

                        // Simpan kelas baru
                        StudentClassHistory::create([
                            'id' => (string) Str::uuid(),
                            'student_id' => $record->id,
                            'class_id' => $data['class_id'],
                            'academic_year_id' => $class->academic_year_id,
                            'is_active' => true,
                        ]);

                        Notification::make()
                            ->title(
                                $record->activeClass
                                    ? 'Berhasil pindah kelas'
                                    : 'Berhasil assign kelas'
                            )
                            ->body("Siswa {$record->name} sekarang berada di kelas {$class->code}")
                            ->success()
                            ->send();
                    }),
            ])

            /* ================= BULK ================= */
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete_student')),

                Tables\Actions\BulkAction::make('bulk_move_class')
                    ->label('Move Class (Massal)')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->visible(fn () => auth()->user()->can('update_student'))
                    ->form([
                        Select::make('class_id')
                            ->label('Pilih Kelas')
                            ->options(fn () =>
                                SchoolClass::with('academicYear')
                                    ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                                    ->get()
                                    ->mapWithKeys(fn ($class) => [
                                        $class->id => "{$class->code} ({$class->academicYear->label})"
                                    ])
                            )
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function ($records, array $data) {
                        $class = SchoolClass::findOrFail($data['class_id']);

                        foreach ($records as $student) {
                            StudentClassHistory::where('student_id', $student->id)
                                ->where('is_active', true)
                                ->update(['is_active' => false]);

                            StudentClassHistory::create([
                                'id' => (string) Str::uuid(),
                                'student_id' => $student->id,
                                'class_id' => $data['class_id'],
                                'academic_year_id' => $class->academic_year_id,
                                'is_active' => true,
                            ]);
                        }

                        Notification::make()
                            ->title('Berhasil memindahkan kelas siswa')
                            ->success()
                            ->send();
                    }),
            ])

            ->checkIfRecordIsSelectableUsing(
                fn () => auth()->user()->can('delete_student')
            );
    }

    /* =====================================================
     | INFOLIST
     ===================================================== */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Data Siswa')
                ->schema([
                    TextEntry::make('nis'),
                    TextEntry::make('name'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('activeClass.classRoom.code')
                        ->label('Kelas Aktif')
                        ->badge()
                        ->default('-'),
                ])
                ->columns(2),
        ]);
    }

    /* =====================================================
     | RELATIONS
     ===================================================== */
    public static function getRelations(): array
    {
        return [
            ClassHistoriesRelationManager::class,
            PaymentHistoryRelationManager::class,
        ];
    }

    /* =====================================================
     | PAGES
     ===================================================== */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    /* =====================================================
     | BACKEND PERMISSION LOCK
     ===================================================== */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_student') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_student') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view_student') ?? false;
    }

    public static function canUpdate(Model $record): bool
    {
        return auth()->user()?->can('update_student') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete_student') ?? false;
    }
}
