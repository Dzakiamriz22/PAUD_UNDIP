<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Models\User;
use App\Models\SchoolClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'Guru & Staff';
    protected static ?string $pluralLabel = 'Guru & Staff';

    /**
     * =====================
     * NAVIGATION VISIBILITY
     * =====================
     */
    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();

        return $u->isSuperAdmin()
            || $u->hasRole('admin')
            || $u->isKepsek();
    }

    /**
     * =====================
     * QUERY: HANYA STAFF
     * =====================
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', fn ($q) =>
                $q->whereIn('name', [
                    'guru',
                    'bendahara',
                    'kepala_sekolah',
                ])
            );
    }

    /**
     * =====================
     * CREATE PERMISSION
     * =====================
     */
    public static function canCreate(): bool
    {
        return auth()->user()->isSuperAdmin()
            || auth()->user()->hasRole('admin');
    }

    /**
     * =====================
     * FORM
     * =====================
     */
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* =====================
             * DATA AKUN
             * ===================== */
            Forms\Components\Section::make('Data Akun')
                ->schema([
                    Forms\Components\TextInput::make('username')
                        ->required()
                        ->unique('users', 'username', ignoreRecord: true)
                        ->disabled(fn () =>
                            auth()->user()->isGuru()
                            || auth()->user()->isBendahara()
                            || auth()->user()->isKepsek()
                        ),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique('users', 'email', ignoreRecord: true)
                        ->disabled(fn () =>
                            auth()->user()->isGuru()
                            || auth()->user()->isBendahara()
                            || auth()->user()->isKepsek()
                        ),

                    Forms\Components\TextInput::make('firstname')
                        ->label('Nama Depan')
                        ->required(),

                    Forms\Components\TextInput::make('lastname')
                        ->label('Nama Belakang')
                        ->required(),
                ])
                ->columns(2),

            /* =====================
             * PERAN (FIX TOTAL)
             * ===================== */
            Forms\Components\Section::make('Peran')
                ->schema([
                    Forms\Components\CheckboxList::make('roles')
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) =>
                                $query->whereIn('name', [
                                    'guru',
                                    'bendahara',
                                    'kepala_sekolah',
                                ])
                        )
                        ->label('Peran')
                        ->reactive()
                        ->afterStateUpdated(function (array $state, Set $set) {

                            // ❌ Larangan rangkap jabatan
                            if (
                                in_array('bendahara', $state)
                                && in_array('kepala_sekolah', $state)
                            ) {
                                Notification::make()
                                    ->title('Peran tidak valid')
                                    ->body('Bendahara tidak boleh merangkap Kepala Sekolah.')
                                    ->danger()
                                    ->send();

                                $set(
                                    'roles',
                                    array_values(array_diff($state, ['kepala_sekolah']))
                                );
                            }
                        })
                        ->disabled(fn () =>
                            !(
                                auth()->user()->isSuperAdmin()
                                || auth()->user()->hasRole('admin')
                            )
                        ),

                ]),

            /* =====================
             * WALI KELAS
             * ===================== */
            Forms\Components\Section::make('Wali Kelas')
                ->schema([
                    Forms\Components\Select::make('homeroom_classes')
                        ->label('Kelas')
                        ->multiple()
                        ->searchable()
                        ->options(
                            SchoolClass::with('academicYear')
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => "{$c->academicYear->label} - {$c->code}",
                                ])
                        )
                        ->helperText('Guru dapat menjadi wali lebih dari satu kelas')
                        ->disabled(fn () =>
                            !(
                                auth()->user()->isSuperAdmin()
                                || auth()->user()->hasRole('admin')
                            )
                        ),
                ]),
        ]);
    }

    /**
     * =====================
     * TABLE
     * =====================
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(', ')
                    ->label('Peran'),

                Tables\Columns\TextColumn::make('homeroomClasses.code')
                    ->badge()
                    ->separator(', ')
                    ->label('Wali Kelas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () =>
                        auth()->user()->isSuperAdmin()
                        || auth()->user()->hasRole('admin')
                        || auth()->user()->isKepsek()
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit'   => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
