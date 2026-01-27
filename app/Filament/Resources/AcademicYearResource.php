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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Tahun Ajaran';
    protected static ?string $pluralLabel = 'Tahun Ajaran';

    /**
     * ======================
     * FORM
     * ======================
     */
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

    /**
     * ======================
     * TABLE
     * ======================
     */
    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
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

                /**
                 * SET AKTIF (CUSTOM ACTION)
                 */
                Tables\Actions\Action::make('setActive')
                    ->label('Set Aktif')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        ! $record->is_active
                        && Auth::user()?->can('set_active_academic::year')
                    )
                    ->action(function ($record) {
                        AcademicYear::where('is_active', true)
                            ->update(['is_active' => false]);

                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('Tahun ajaran berhasil diaktifkan')
                            ->success()
                            ->send();
                    }),

                /**
                 * EDIT
                 */
                Tables\Actions\EditAction::make()
                    ->visible(fn () =>
                        Auth::user()?->can('update_academic::year')
                    ),

                /**
                 * DELETE (jika suatu saat diaktifkan)
                 */
                // Tables\Actions\DeleteAction::make()
                //     ->visible(fn () =>
                //         Auth::user()?->can('delete_academicyear')
                //     ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () =>
                        Auth::user()?->can('delete_academic::year')
                    ),
            ]);
    }

    /**
     * ======================
     * PAGES
     * ======================
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit'   => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }

    /**
     * ======================
     * PERMISSION GUARD
     * ======================
     */

    // Sidebar
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check()
            && Auth::user()->can('view_any_academic::year');
    }

    // Index
    public static function canViewAny(): bool
    {
        return Auth::check()
            && Auth::user()->can('view_any_academic::year');
    }

    // View detail
    public static function canView(Model $record): bool
    {
        return Auth::check()
            && Auth::user()->can('view_academic::year');
    }

    // Create
    public static function canCreate(): bool
    {
        return Auth::check()
            && Auth::user()->can('create_academic::year');
    }

    // Update
    public static function canUpdate(Model $record): bool
    {
        return Auth::check()
            && Auth::user()->can('update_academic::year');
    }

    // Delete
    public static function canDelete(Model $record): bool
    {
        return Auth::check()
            && Auth::user()->can('delete_academic::year');
    }
}
