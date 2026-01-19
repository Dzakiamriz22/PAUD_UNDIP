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

    /* =====================================================
     |  KONSTANTA (TANPA FILE BARU)
     ===================================================== */
    public const CLASS_CATEGORIES = [
        'TK' => 'TK',
        'KB' => 'KB',
        'TPA_PAUD' => 'TPA PAUD',
        'TPA_SD' => 'TPA SD',
        'TPA_TK' => 'TPA + TK',
        'TPA_KB' => 'TPA + KB',
    ];

    public const BILLING_TYPES = [
        'once'    => 'Sekali Bayar',
        'monthly' => 'Bulanan',
        'yearly'  => 'Tahunan',
        'daily'   => 'Harian',
        'penalty' => 'Denda',
    ];

    /* =====================================================
     |  FORM
     ===================================================== */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Tarif')
                ->schema([
                    Forms\Components\Select::make('income_type_id')
                        ->label('Jenis Pendapatan')
                        ->relationship('incomeType', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('class_category')
                        ->label('Kategori Kelas')
                        ->options(self::CLASS_CATEGORIES)
                        ->required(),

                    Forms\Components\Select::make('billing_type')
                        ->label('Jenis Pembayaran')
                        ->options(self::BILLING_TYPES)
                        ->required(),

                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal Tarif')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Hidden::make('proposed_by')
                ->default(fn () => Auth::id()),
        ]);
    }

    /* =====================================================
     |  TABLE
     ===================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incomeType.name')
                    ->label('Jenis Pendapatan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('class_category')
                    ->label('Kategori Kelas')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::CLASS_CATEGORIES[$state] ?? $state),

                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Jenis Pembayaran')
                    ->formatStateUsing(fn ($state) => self::BILLING_TYPES[$state] ?? '-')
                    ->badge(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('approved_at')
                    ->label('Disetujui')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            
            /* ================= GROUPING ================= */
            ->groups([
                Tables\Grouping\Group::make('class_category')
                    ->label('Kategori Kelas')
                    ->collapsible()
                    ->getTitleFromRecordUsing(function (Tariff $record) {
                        return self::CLASS_CATEGORIES[$record->class_category] ?? $record->class_category;
                    }),
            ])

            /* ================= FILTER ================= */
            ->filters([
                Tables\Filters\SelectFilter::make('income_type_id')
                    ->label('Jenis Pendapatan')
                    ->relationship('incomeType', 'name'),

                Tables\Filters\SelectFilter::make('class_category')
                    ->label('Kategori Kelas')
                    ->options(self::CLASS_CATEGORIES),

                Tables\Filters\SelectFilter::make('billing_type')
                    ->label('Jenis Pembayaran')
                    ->options(self::BILLING_TYPES),

                Tables\Filters\TernaryFilter::make('approved_at')
                    ->label('Status Persetujuan')
                    ->nullable()
                    ->trueLabel('Disetujui')
                    ->falseLabel('Belum Disetujui'),
            ])

            /* ================= ACTION ================= */
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Tariff $record) => is_null($record->approved_at))
                    ->action(fn (Tariff $record) => $record->update([
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ])),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Tariff $record) => is_null($record->approved_at)),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Tariff $record) => is_null($record->approved_at)),
            ])

            ->defaultSort('created_at', 'desc')
            ->defaultGroup('class_category');
    }

    /* =====================================================
     |  QUERY
     ===================================================== */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('incomeType');
    }

    /* =====================================================
     |  PAGES
     ===================================================== */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
}