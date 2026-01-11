<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Data Sekolah';
    protected static ?string $pluralLabel = 'Data Sekolah';

    /**
     * 🔒 Hanya Admin & Super Admin
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->hasRole('admin');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->hasRole('admin');
    }

    public static function canDelete($record): bool
    {
        return false; // ❌ biasanya data sekolah tidak dihapus
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Sekolah')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Sekolah')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->label('Alamat')
                        ->rows(3),

                    Forms\Components\TextInput::make('contact')
                        ->label('Kontak')
                        ->placeholder('Telepon / Email'),

                    Forms\Components\TextInput::make('logo_url')
                        ->label('URL Logo')
                        ->url()
                        ->helperText('URL logo sekolah (opsional)'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact')
                    ->label('Kontak'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }
}