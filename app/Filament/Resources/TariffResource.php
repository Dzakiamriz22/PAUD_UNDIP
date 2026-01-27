<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Tarif';
    protected static ?string $pluralLabel = 'Tarif';

    /* =====================================================
     |  KONSTANTA
     ===================================================== */
    public const CLASS_CATEGORIES = [
        'TK'       => 'TK',
        'KB'       => 'KB',
        'TPA_PAUD' => 'TPA PAUD',
        'TPA_SD'   => 'TPA SD',
        'TPA_TK'   => 'TPA + TK',
        'TPA_KB'   => 'TPA + KB',
    ];

    public const BILLING_TYPES = [
        'once'    => 'Sekali Bayar',
        'monthly' => 'Bulanan',
        'yearly'  => 'Tahunan',
        'daily'   => 'Harian',
        'penalty' => 'Denda',
    ];

    /* =====================================================
     |  AKSES
     ===================================================== */
    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_tariff') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->hasRole(['admin', 'bendahara'])
            && in_array($record->status, ['pending', 'rejected']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_tariff') ?? false;
    }

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
                ])
                ->columns(2),

            /* ===== ALASAN PENOLAKAN ===== */
            Forms\Components\Section::make('Alasan Penolakan')
                ->schema([
                    Forms\Components\Textarea::make('rejection_note')
                        ->label('Catatan Kepala Sekolah')
                        ->rows(4)
                        ->disabled() // hanya untuk dibaca admin/bendahara
                        ->visible(fn ($record) => $record?->status === 'rejected'),
                ])
                ->visible(fn ($record) => $record?->status === 'rejected'),

            /* ===== FIELD SISTEM ===== */
            Forms\Components\Hidden::make('status')
                ->default('pending'),

            Forms\Components\Hidden::make('is_active')
                ->default(false),

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
                    ->formatStateUsing(fn ($state) =>
                        self::CLASS_CATEGORIES[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Jenis Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn ($state) =>
                        self::BILLING_TYPES[$state] ?? '-'
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'  => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default    => $state,
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])

            ->actions([
                /* ===== APPROVE ===== */
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'pending'
                        && Auth::user()->hasRole('kepala_sekolah')
                    )
                    ->action(fn (Tariff $record) => $record->update([
                        'status'         => 'approved',
                        'approved_by'    => Auth::id(),
                        'approved_at'    => now(),
                        'rejection_note' => null,
                    ])),

                /* ===== REJECT DENGAN ALASAN ===== */
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'pending'
                        && Auth::user()->hasRole('kepala_sekolah')
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Tariff $record, array $data) {
                        $record->update([
                            'status'         => 'rejected',
                            'rejection_note' => $data['rejection_note'],
                        ]);
                    }),

                /* ===== AKTIF / NONAKTIF ===== */
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Tariff $record) =>
                        $record->is_active ? 'Nonaktifkan' : 'Aktifkan'
                    )
                    ->icon(fn (Tariff $record) =>
                        $record->is_active
                            ? 'heroicon-o-x-circle'
                            : 'heroicon-o-check-circle'
                    )
                    ->color(fn (Tariff $record) =>
                        $record->is_active ? 'danger' : 'success'
                    )
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'approved'
                        && Auth::user()->hasRole(['admin', 'bendahara'])
                    )
                    ->action(fn (Tariff $record) =>
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ])
                    ),

                /* ===== EDIT (AJUKAN ULANG) ===== */
                Tables\Actions\EditAction::make()
                    ->visible(fn (Tariff $record) =>
                        Auth::user()->hasRole(['admin', 'bendahara'])
                        && in_array($record->status, ['pending', 'rejected'])
                    )
                    ->mutateFormDataUsing(function (array $data) {
                        $data['status'] = 'pending';
                        $data['rejection_note'] = null; // reset catatan
                        return $data;
                    }),

                /* ===== DELETE ===== */
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () =>
                        Auth::user()->hasRole(['admin', 'bendahara'])
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /* =====================================================
     |  QUERY
     ===================================================== */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('incomeType');
    }

    /* =====================================================
     |  PAGES
     ===================================================== */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit'   => Pages\EditTariff::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_tariff') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view_tariff') ?? false;
    }

    public static function canUpdate(Model $record): bool
    {
        return auth()->user()?->can('update_tariff') ?? false;
    }
}
