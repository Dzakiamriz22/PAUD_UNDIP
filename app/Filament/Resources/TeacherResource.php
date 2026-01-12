<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Models\User;
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

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'Guru & Staff';
    protected static ?string $pluralLabel     = 'Guru & Staff';

    /* =====================================================
     | NAVIGATION VISIBILITY
     ===================================================== */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->isKepsek();
    }

    /* =====================================================
     | QUERY : HANYA USER STAFF
     ===================================================== */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', [
                    'guru',
                    'bendahara',
                    'kepala_sekolah',
                ]);
            });
    }

    /* =====================================================
     | CREATE PERMISSION
     ===================================================== */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasRole('admin');
    }

    /* =====================================================
     | FORM
     ===================================================== */
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* -------------------------------
             | DATA AKUN
             -------------------------------- */
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

            /* -------------------------------
             | PERAN (TIDAK BOLEH RANGKAP)
             -------------------------------- */
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
                        ->columns(2)
                        ->reactive()

                        ->disableOptionWhen(function (string $value, array $state): bool {
                            return
                                ($value === 'bendahara' && in_array('kepala_sekolah', $state)) ||
                                ($value === 'kepala_sekolah' && in_array('bendahara', $state));
                        })

                        ->afterStateUpdated(function (array $state, Set $set) {
                            if (
                                in_array('bendahara', $state)
                                && in_array('kepala_sekolah', $state)
                            ) {
                                Notification::make()
                                    ->title('Peran tidak valid')
                                    ->body('Bendahara dan Kepala Sekolah tidak boleh dirangkap.')
                                    ->danger()
                                    ->send();

                                $set(
                                    'roles',
                                    array_values(
                                        array_diff($state, ['kepala_sekolah'])
                                    )
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
        ]);
    }

    /* =====================================================
     | TABLE
     ===================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->separator(', '),

                Tables\Columns\TextColumn::make('homeroomClasses.code')
                    ->label('Wali Kelas')
                    ->badge()
                    ->separator(', '),
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

    /* =====================================================
     | PAGES
     ===================================================== */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit'   => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}