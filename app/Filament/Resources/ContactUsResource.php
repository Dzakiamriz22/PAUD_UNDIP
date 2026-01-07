<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactUsResource\Actions\ReplyAction;
use App\Filament\Resources\ContactUsResource\Pages;
use App\Models\ContactUs;
use Filament\Tables\Actions as TablesActions;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Support\Facades\Auth;

class ContactUsResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = ContactUs::class;
    protected static ?string $slug = 'contact-us/inbox';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationIcon = 'fluentui-mail-inbox-28';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('firstname'),
                        Infolists\Components\TextEntry::make('lastname'),
                        Infolists\Components\TextEntry::make('email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('phone')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('company'),
                        Infolists\Components\TextEntry::make('employees')
                            ->formatStateUsing(fn(?string $state): ?string => $state ? $state . ' Employees' : null),
                        Infolists\Components\TextEntry::make('title')
                            ->label('Job Title'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->visible(fn() => function () {
                                /** @var \App\Models\User $user */
                                $user = Auth::user();
                                $user->can('viewConfidential', ContactUs::class);
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Message')
                    ->schema([
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Subject'),
                        Infolists\Components\TextEntry::make('message')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Received'),
                        Infolists\Components\TextEntry::make('metadata.source')
                            ->label('Source'),
                        Infolists\Components\TextEntry::make('metadata.utm_source')
                            ->label('Campaign Source'),
                        Infolists\Components\TextEntry::make('metadata.utm_medium')
                            ->label('Campaign Medium'),
                        Infolists\Components\TextEntry::make('metadata.utm_campaign')
                            ->label('Campaign Name'),
                        Infolists\Components\TextEntry::make('metadata.referrer')
                            ->label('Referrer URL'),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->visible(fn() => function () {
                                /** @var \App\Models\User $user */
                                $user = Auth::user();
                                $user->can('viewConfidential', ContactUs::class);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Show reply section if a reply has been sent
                Infolists\Components\Section::make('Your Reply')
                    ->schema([
                        Infolists\Components\TextEntry::make('reply_subject')
                            ->label('Subject'),
                        Infolists\Components\TextEntry::make('replied_at')
                            ->dateTime()
                            ->label('Replied At'),
                        Infolists\Components\TextEntry::make('repliedBy.firstname')
                            ->label('Replied By')
                            ->formatStateUsing(function ($state, ContactUs $record) {
                                return $record->repliedBy
                                    ? "{$record->repliedBy->firstname} {$record->repliedBy->lastname}"
                                    : 'System';
                            }),
                        Infolists\Components\TextEntry::make('reply_message')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(
                        fn(ContactUs $record): bool =>
                        !empty($record->reply_message) && !empty($record->reply_subject)
                    )
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('fullname')
                            ->weight('bold')
                            ->searchable()
                            ->limit(20)
                            ->sortable(),
                        Tables\Columns\TextColumn::make('email')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('phone')
                            ->searchable()
                            ->toggledHiddenByDefault(),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('subject')
                            ->limit(30)
                            ->searchable(),
                        Tables\Columns\TextColumn::make('company')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('employees')
                            ->formatStateUsing(fn(?string $state): ?string => $state ?? '')
                            ->searchable()
                            ->toggledHiddenByDefault(),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('created_at')
                            ->sortable()
                            ->searchable()
                            ->dateTime()
                            ->label('Sent at'),
                        Tables\Columns\TextColumn::make('status')
                            ->formatStateUsing(fn(string $state): string => ucfirst($state))
                            ->sortable()
                            ->searchable()
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'new' => 'danger',
                                'read' => 'warning',
                                'responded' => 'success',
                                'pending' => 'info',
                                'closed' => 'gray',
                                default => '',
                            }),
                    ]),
                ])
            ])
            ->recordClasses(fn(ContactUs $record) => match ($record->status) {
                'new' => 'border-s-2 border-danger-600 dark:border-danger-300',
                'read' => 'border-s-2 border-warning-600 dark:border-warning-300',
                'responded' => 'border-s-2 border-success-600 dark:border-success-300',
                'pending' => 'border-s-2 border-info-600 dark:border-info-300',
                default => '',
            })
            ->filters([
                // TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options(function ($livewire): array {
                        $status_options = ContactUs::getStatusOptions();
                        return $status_options;
                    })
            ])
            ->bulkActions([
                TablesActions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Trash selected')
                        ->visible(fn($livewire): bool => $livewire->activeTab !== 'trashed'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->color('success')
                        ->visible(fn($livewire): bool => $livewire->activeTab === 'trashed'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn($livewire): bool => $livewire->activeTab === 'trashed'),
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'new') {
                                    $record->markAsRead();
                                    $count++;
                                }
                            }

                            if ($count > 0) {
                                Notification::make()
                                    ->title("Marked {$count} messages as read")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->actions([
                ReplyAction::make()
                    ->visible(
                        fn(ContactUs $record): bool =>
                        empty($record->reply_message) || empty($record->reply_subject)
                    ),
                TablesActions\ActionGroup::make([
                    TablesActions\ViewAction::make()
                        ->mutateRecordDataUsing(function (array $data, ContactUs $record): array {
                            // if ($record->status === 'new') {
                            //     $data['status'] = 'read';
                            //     $record->markAsRead();
                            // }
                            return $data;
                        })->extraModalFooterActions(
                            [
                                Tables\Actions\RestoreAction::make()
                                    ->color('success')
                                    ->cancelParentActions()
                                    ->deselectRecordsAfterCompletion()
                                    ->visible(fn(ContactUs $contact_us): bool => $contact_us->trashed()),
                                Tables\Actions\ForceDeleteAction::make()
                                    ->cancelParentActions()
                                    ->deselectRecordsAfterCompletion()
                                    ->visible(fn(ContactUs $contact_us): bool => $contact_us->trashed()),
                                TablesActions\Action::make('markAsRead')
                                    ->label('Mark as Read')
                                    ->icon('heroicon-o-check')
                                    ->action(function (ContactUs $record): void {
                                        if ($record->status === 'new') {
                                            $record->markAsRead();
                                            $record->refresh();

                                            Notification::make()
                                                ->title('Message marked as read')
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('Message already read')
                                                ->info()
                                                ->send();
                                        }
                                    })
                                    ->visible(fn(ContactUs $record): bool => $record->status === 'new'),
                            ]
                        ),
                    // TablesActions\Action::make('markAsRead')
                    //     ->label('Mark as Read')
                    //     ->icon('heroicon-o-check')
                    //     ->action(function (ContactUs $record): void {
                    //         if ($record->status === 'new') {
                    //             $record->markAsRead();
                    //             $record->refresh();

                    //             Notification::make()
                    //                 ->title('Message marked as read')
                    //                 ->success()
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->title('Message already read')
                    //                 ->info()
                    //                 ->send();
                    //         }
                    //     })
                    //     ->visible(fn (ContactUs $record): bool => $record->status === 'new'),
                    TablesActions\DeleteAction::make('delete')->label('Trash'),
                    TablesActions\ForceDeleteAction::make(),
                    TablesActions\RestoreAction::make()->color('success'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactUs::route('/'),
        ];
    }

    public static function getLabel(): string
    {
        return 'Inbox';
    }

    public static function getPluralLabel(): string
    {
        return 'Contact Us';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'new')->count();
    }

    public static function getNavigationLabel(): string
    {
        return 'Inbox';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    // {
    //     return $record->fullname;
    // }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'firstname', 'lastname'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Status' => $record->status,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ContactUsResource::getUrl('index', ['tableSearch' => $record->name]);
    }
}
