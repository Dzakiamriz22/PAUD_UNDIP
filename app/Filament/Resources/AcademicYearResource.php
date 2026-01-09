<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Tahun Ajaran';
    protected static ?string $pluralLabel = 'Tahun Ajaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('year')
                ->label('Tahun Ajaran')
                ->placeholder('2024/2025')
                ->required()
                ->maxLength(9),

            Forms\Components\Select::make('semester')
                ->label('Semester')
                ->options([
                    'ganjil' => 'Ganjil',
                    'genap' => 'Genap',
                ])
                ->required(),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktifkan')
                ->helperText('Hanya satu tahun ajaran yang boleh aktif')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('semester')
                    ->label('Semester')
                    ->colors([
                        'primary' => 'ganjil',
                        'warning' => 'genap',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Status')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Nonaktif'),
            ])
            ->defaultSort('is_active', 'desc')
            ->actions([
                Tables\Actions\Action::make('setActive')
                    ->label('Set Aktif')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->is_active)
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('Tahun ajaran berhasil diaktifkan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => ! $record->is_active)
                    ->disabled(fn ($record) => $record->schoolClasses()->exists())
                    ->tooltip('Tidak bisa dihapus jika sudah digunakan oleh kelas'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->disabled(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
}