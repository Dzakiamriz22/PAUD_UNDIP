<?php

namespace App\Filament\Resources\Banner\ContentResource\Pages;

use App\Filament\Resources\Banner\ContentResource;
use App\Models\Banner\Content;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\Url;

class ViewContent extends ViewRecord
{
    protected static string $resource = ContentResource::class;

    #[Url]
    public ?string $page = null;

    #[Url]
    public ?string $activeTab = null;

    #[Url]
    public ?array $tableFilters = null;

    #[Url]
    public ?string $tableSearch = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')  
                ->url(fn (): string => 
                    static::getResource()::getUrl('edit', 
                        [
                            'record' => $this->record, 
                            'page' => $this->page, 
                            'activeTab' => $this->activeTab, 
                            'tableFilters' => $this->tableFilters, 
                            'tableSearch' => $this->tableSearch
                        ]))
                ->visible(fn(Content $content): bool => !$content->trashed()),
            Actions\DeleteAction::make()
                ->label('Trash')
                ->icon('heroicon-o-trash'),
            Actions\RestoreAction::make()
                ->icon(FilamentIcon::resolve('actions::restore-action') ?? 'heroicon-m-arrow-uturn-left')
                ->color('success')
                ->successRedirectUrl(fn (): string => 
                    static::getResource()::getUrl('index', 
                        [
                            'record' => $this->record, 
                            'page' => $this->page, 
                            'activeTab' => $this->activeTab, 
                            'tableFilters' => $this->tableFilters, 
                            'tableSearch' => $this->tableSearch
                        ])),
            Actions\ForceDeleteAction::make()->icon('heroicon-o-trash'),
            Actions\Action::make('list')
                ->label('Back')
                ->color('gray')
                ->icon('heroicon-o-chevron-left')
                ->url(fn (): string => 
                    static::getResource()::getUrl('index', 
                        [
                            'page' => $this->page, 
                            'activeTab' => $this->activeTab, 
                            'tableFilters' => $this->tableFilters, 
                            'tableSearch' => $this->tableSearch
                        ])),
        ];
    }

    public function getTitle(): string
    {
        return __('View Content');
    }

    // public static function getEloquentQuery(): Builder {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }

    public function mount(int | string $record): void
    {
        $this->record = static::getResource()::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->findOrFail($record);

        $this->authorizeAccess();

        if (! $this->hasInfolist()) {
            $this->fillForm();
        }
    }
}
