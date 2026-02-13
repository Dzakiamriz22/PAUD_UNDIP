<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VirtualAccountResource\Pages;
use App\Models\VirtualAccount;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Model;

class VirtualAccountResource extends Resource
{
    protected static ?string $model = VirtualAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Virtual Accounts';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('bank_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('va_number')
                    ->required()
                    ->maxLength(255)
                    ->label('VA Number'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\MultiSelect::make('income_types')
                    ->label('Income Types')
                    ->relationship('incomeTypes', 'name')
                    ->preload()
                    ->helperText('Select which income types this VA applies to'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->extraHeaderAttributes(['class' => 'px-2 py-1 text-xs'])
                    ->extraCellAttributes(['class' => 'px-2 py-1 text-sm']),

                TextColumn::make('bank_name')
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['class' => 'px-2 py-1 text-xs'])
                    ->extraCellAttributes(['class' => 'px-2 py-1 text-sm']),

                TextColumn::make('va_number')
                    ->searchable()
                    ->label('VA Number')
                    ->extraHeaderAttributes(['class' => 'px-2 py-1 text-xs'])
                    ->extraCellAttributes(['class' => 'px-2 py-1 text-sm']),

                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    ->extraHeaderAttributes(['class' => 'px-2 py-1 text-xs'])
                    ->extraCellAttributes(['class' => 'px-2 py-1 text-sm']),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created')
                    ->sortable()
                    ->extraHeaderAttributes(['class' => 'px-2 py-1 text-xs'])
                    ->extraCellAttributes(['class' => 'px-2 py-1 text-sm']),
            ])
            ->filters([
                // add filters if needed
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
            Tables\Actions\ViewAction::make()
                ->visible(fn () => auth()->user()?->can('view_virtual::account')),

            Tables\Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('update_virtual::account')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->can('delete_virtual::account')),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Set Active')
                    ->visible(fn () => static::canToggleActive())
                    ->action(fn ($records) => $records->each->update(['is_active' => true]))
                    ->requiresConfirmation()
                    ->color('success'),

                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Set Inactive')
                    ->visible(fn () => static::canToggleActive())
                    ->action(fn ($records) => $records->each->update(['is_active' => false]))
                    ->requiresConfirmation()
                    ->color('danger'),
            ]);
    }

    // Return only one record per unique bank_name + va_number (choose smallest id)
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $sub = \DB::table('virtual_accounts')
            ->selectRaw('MIN(id) as id')
            ->groupBy('bank_name', 'va_number');

        return parent::getEloquentQuery()->whereIn('id', function ($query) use ($sub) {
            $query->select('id')->fromSub($sub, 'sub_ids');
        })->with('incomeTypes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVirtualAccounts::route('/'),
            'create' => Pages\CreateVirtualAccount::route('/create'),
            'edit' => Pages\EditVirtualAccount::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_virtual::account') ?? false;
    }

    public static function canCreate(): bool
    {
        if (! (auth()->user()?->can('create_virtual::account') ?? false)) {
            return false;
        }

        return VirtualAccount::count() === 0;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view_virtual::account') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete_virtual::account') ?? false;
    }

    public static function canUpdate(Model $record): bool
    {
        return auth()->user()?->can('update_virtual::account') ?? false;
    }

    protected static function canToggleActive(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'bendahara']) ?? false;
    }
}
