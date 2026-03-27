<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\RawJs;

class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Tarif';
    protected static ?string $pluralLabel = 'Tarif';
    protected static ?int $navigationSort = 2;

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
        return Auth::user()->hasAnyRole(['operator', 'bendahara', config('filament-shield.super_admin.name')])
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
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
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

                // Tampilkan cuplikan alasan penolakan jika status = rejected
                Tables\Columns\TextColumn::make('rejection_note')
                    ->label('Alasan Penolakan')
                    ->toggleable()
                    ->wrap()
                    ->limit(80)
                    ->visible(fn ($record) => $record?->status === 'rejected'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])

            ->actions([
                Tables\Actions\Action::make('approval_history')
                    ->label('Riwayat Approval')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Riwayat Approval Tarif')
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Placeholder::make('approval_history')
                            ->label('')
                            ->content(fn (Tariff $record): HtmlString => self::renderApprovalHistory($record)),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Tables\Actions\Action::make('lihat_alasan')
                    ->label('Lihat Alasan')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('secondary')
                    ->modal()
                    ->modalHeading('Alasan Penolakan')
                    ->modalDescription(fn (Tariff $record) => $record->rejection_note)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('OK')
                    ->visible(fn (Tariff $record) => $record->status === 'rejected'),

                /* ===== EDIT ALASAN (KEPALA SEKOLAH) ===== */
                Tables\Actions\Action::make('edit_alasan')
                    ->label('Edit Alasan')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'rejected'
                        && Auth::user()->hasRole('kepala_sekolah')
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Tariff $record, array $data) {
                        $oldNote = $record->rejection_note;

                        $record->update([
                            'rejection_note' => $data['rejection_note'],
                        ]);

                        self::storeApprovalHistory(
                            $record,
                            action: 'rejection_note_updated',
                            fromStatus: $record->status,
                            toStatus: $record->status,
                            note: sprintf('Alasan penolakan diubah. Sebelumnya: %s', $oldNote ?: '-')
                        );
                    }),

                /* ===== APPROVE ===== */
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'pending'
                        && Auth::user()->hasAnyRole(['kepala_sekolah', config('filament-shield.super_admin.name')])
                    )
                    ->action(function (Tariff $record) {
                        $fromStatus = $record->status;

                        $record->update([
                            'status'         => 'approved',
                            'approved_by'    => Auth::id(),
                            'approved_at'    => now(),
                            'rejection_note' => null,
                        ]);

                        self::storeApprovalHistory(
                            $record,
                            action: 'approved',
                            fromStatus: $fromStatus,
                            toStatus: 'approved',
                            note: 'Tarif disetujui.'
                        );
                    }),

                /* ===== REJECT DENGAN ALASAN ===== */
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'pending'
                        && Auth::user()->hasAnyRole(['kepala_sekolah', config('filament-shield.super_admin.name')])
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Tariff $record, array $data) {
                        $fromStatus = $record->status;

                        $record->update([
                            'status'         => 'rejected',
                            'rejection_note' => $data['rejection_note'],
                        ]);

                        self::storeApprovalHistory(
                            $record,
                            action: 'rejected',
                            fromStatus: $fromStatus,
                            toStatus: 'rejected',
                            note: $data['rejection_note']
                        );
                    }),

                /* ===== PERBAIKI / AJUKAN ULANG ===== */
                Tables\Actions\Action::make('perbaiki')
                    ->label('Perbaiki / Ajukan Ulang')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (Tariff $record) =>
                        $record->status === 'rejected' && (
                            Auth::id() === $record->proposed_by
                            || Auth::user()->hasAnyRole(['operator', 'bendahara', config('filament-shield.super_admin.name')])
                        )
                    )
                    ->modal()
                    ->modalHeading('Perbaiki / Ajukan Ulang Tarif')
                    ->form([
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
                    ->action(function (Tariff $record, array $data) {
                        $fromStatus = $record->status;

                        $record->update([
                            'income_type_id' => $data['income_type_id'],
                            'class_category' => $data['class_category'],
                            'billing_type'   => $data['billing_type'],
                            'amount'         => $data['amount'],
                            'status'         => 'pending',
                            'rejection_note' => null,
                        ]);

                        self::storeApprovalHistory(
                            $record,
                            action: 'resubmitted',
                            fromStatus: $fromStatus,
                            toStatus: 'pending',
                            note: 'Tarif diperbaiki dan diajukan ulang.'
                        );
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
                        && Auth::user()->hasRole(['operator', 'bendahara'])
                    )
                    ->action(fn (Tariff $record) =>
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ])
                    ),

                /* ===== DELETE ===== */
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () =>
                        Auth::user()->hasRole(['operator', 'bendahara'])
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function storeApprovalHistory(
        Tariff $record,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $note = null
    ): void {
        $record->approvalHistories()->create([
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'acted_by' => Auth::id(),
            'acted_at' => now(),
        ]);
    }

    protected static function renderApprovalHistory(Tariff $record): HtmlString
    {
        self::ensureApprovalHistoryExists($record);

        $histories = $record->approvalHistories()->with('actor')->orderByDesc('acted_at')->get();

        if ($histories->isEmpty()) {
            return new HtmlString('<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">Belum ada riwayat approval.</div>');
        }

        $items = $histories->map(function ($history) {
            $actorName = e($history->actor?->name ?? $history->actor?->username ?? 'Sistem');
            $actedAt = e(optional($history->acted_at)->format('d M Y H:i') ?? '-');
            $actionLabel = e(self::historyActionLabel((string) $history->action));
            $statusFlow = self::historyStatusFlow(
                $history->from_status,
                $history->to_status,
            );
            $note = $history->note
                ? '<div class="mt-2 rounded-md bg-gray-50 p-2 text-sm text-gray-700"><span class="font-semibold">Catatan:</span> ' . e((string) $history->note) . '</div>'
                : '';

            return '<li class="relative border-l-2 border-gray-200 pl-4 pb-4">'
                . '<div class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-primary-600"></div>'
                . '<div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">'
                . '<div class="flex flex-wrap items-center justify-between gap-2">'
                . '<span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700">' . $actionLabel . '</span>'
                . '<span class="text-xs text-gray-500">' . $actedAt . '</span>'
                . '</div>'
                . '<div class="mt-2 text-sm text-gray-800">' . $statusFlow . '</div>'
                . '<div class="mt-1 text-sm text-gray-600">Oleh: <span class="font-medium text-gray-900">' . $actorName . '</span></div>'
                . $note
                . '</div>'
                . '</li>';
        })->implode('');

        $html = '<div class="space-y-3">'
            . '<div class="rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm text-primary-900">'
            . '<span class="font-semibold">Total riwayat:</span> ' . $histories->count() . ' aktivitas'
            . '</div>'
            . '<ol class="max-h-[28rem] overflow-y-auto pr-2">' . $items . '</ol>'
            . '</div>';

        return new HtmlString($html);
    }

    protected static function historyActionLabel(string $action): string
    {
        return match ($action) {
            'submitted' => 'Diajukan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'resubmitted' => 'Diajukan Ulang',
            'rejection_note_updated' => 'Alasan Diubah',
            default => ucwords(str_replace('_', ' ', $action)),
        };
    }

    protected static function historyStatusFlow(?string $fromStatus, ?string $toStatus): string
    {
        if (! $fromStatus && ! $toStatus) {
            return '<span class="text-gray-500">Tanpa perubahan status</span>';
        }

        $from = self::historyStatusLabel($fromStatus);
        $to = self::historyStatusLabel($toStatus);

        return '<span class="inline-flex items-center gap-2">'
            . '<span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">' . e($from) . '</span>'
            . '<span class="text-gray-500">-></span>'
            . '<span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">' . e($to) . '</span>'
            . '</span>';
    }

    protected static function historyStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            null => '-',
            default => $status,
        };
    }

    protected static function ensureApprovalHistoryExists(Tariff $record): void
    {
        if ($record->approvalHistories()->exists()) {
            return;
        }

        $record->approvalHistories()->create([
            'action' => 'submitted',
            'from_status' => null,
            'to_status' => 'pending',
            'note' => 'Tarif diajukan untuk persetujuan.',
            'acted_by' => $record->proposed_by,
            'acted_at' => $record->created_at,
        ]);

        if ($record->status === 'approved') {
            $record->approvalHistories()->create([
                'action' => 'approved',
                'from_status' => 'pending',
                'to_status' => 'approved',
                'note' => 'Tarif disetujui.',
                'acted_by' => $record->approved_by,
                'acted_at' => $record->approved_at ?? $record->updated_at,
            ]);
        }

        if ($record->status === 'rejected') {
            $record->approvalHistories()->create([
                'action' => 'rejected',
                'from_status' => 'pending',
                'to_status' => 'rejected',
                'note' => $record->rejection_note,
                'acted_by' => $record->approved_by,
                'acted_at' => $record->updated_at,
            ]);
        }
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
