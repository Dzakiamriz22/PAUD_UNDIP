<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Models\User;
use App\Models\SchoolClass;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'Guru & Staff';
    protected static ?string $pluralLabel = 'Guru & Staff';

    /**
     * 🔐 Hanya admin & super admin
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * 🎯 Filter hanya user dengan role tertentu
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pegawai')
                ->schema([
                    Forms\Components\TextInput::make('firstname')
                        ->label('Nama Depan')
                        ->required(),

                    Forms\Components\TextInput::make('lastname')
                        ->label('Nama Belakang')
                        ->required(),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\CheckboxList::make('roles')
                        ->label('Jabatan')
                        ->options([
                            'guru' => 'Guru',
                            'bendahara' => 'Bendahara',
                            'kepala_sekolah' => 'Kepala Sekolah',
                        ])
                        ->required()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (in_array('kepala_sekolah', $state) && in_array('bendahara', $state)) {
                                $set('roles', array_diff($state, ['bendahara']));
                            }
                        })
                        ->dehydrated(false),

                    Forms\Components\Select::make('homeroom_classes')
                        ->label('Wali Kelas')
                        ->multiple()
                        ->options(
                            \App\Models\SchoolClass::pluck('code', 'id')
                        )
                        ->visible(fn ($get) => in_array('guru', $get('roles') ?? []))
                        ->dehydrated(false),

                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Jabatan')
                    ->badge()
                    ->separator(', '),

                Tables\Columns\TextColumn::make('homeroomClass.code')
                    ->label('Wali Kelas')
                    ->default('-'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Nonaktifkan'),
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
