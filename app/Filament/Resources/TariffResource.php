<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use App\Models\IncomeType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Tarif';
    protected static ?string $pluralLabel = 'Tarif';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('income_type_id')
                    ->label('Jenis Pendapatan')
                    ->options(
                        IncomeType::query()
                            ->where('is_discount', false)
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('class_category')
                    ->label('Kategori Kelas')
                    ->required()
                    ->options([
                        'TK' => 'TK',
                        'KB' => 'KB',
                        'TPA_PAUD' => 'TPA PAUD',
                        'TPA_SD' => 'TPA SD',
                        'TPA_TK' => 'TPA + TK',
                        'TPA_KB' => 'TPA + KB',
                    ]),

                Forms\Components\Select::make('billing_type')
                    ->label('Jenis Pembayaran')
                    ->required()
                    ->options([
                        'once' => 'Sekali Bayar',
                        'monthly' => 'Bulanan',
                        'yearly' => 'Tahunan',
                    ]),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Tarif')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                Forms\Components\Hidden::make('proposed_by')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('income_type_id')
                    ->label('Jenis Pendapatan')
                    ->state(fn (Tariff $record) => $record->incomeType?->name ?? '-'),

                Tables\Columns\TextColumn::make('class_category')
                    ->label('Kategori Kelas')
                    ->badge(),

                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Pembayaran')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'once' => 'Sekali',
                        'monthly' => 'Bulanan',
                        'yearly' => 'Tahunan',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR'),

                Tables\Columns\IconColumn::make('approved_at')
                    ->label('Disetujui')
                    ->boolean(fn ($record) => $record->approved_at !== null),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => is_null($record->approved_at))
                    ->action(function (Tariff $record) {
                        $record->update([
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => is_null($record->approved_at)),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => is_null($record->approved_at)),
            ])
            ->defaultSort('created_at', 'desc');
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('incomeType');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
}