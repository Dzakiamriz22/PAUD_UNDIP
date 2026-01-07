<?php

namespace App\Filament\Resources\Banner\ContentCategoryResource\Pages;

use App\Filament\Resources\Banner\ContentCategoryResource;
use App\Models\Banner\Category;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\Url;

class ViewCategory extends ViewRecord
{
    protected static string $resource = ContentCategoryResource::class;

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
                ->url(fn(): string =>
                static::getResource()::getUrl(
                    'edit',
                    [
                        'record' => $this->record,
                        'page' => $this->page,
                        'activeTab' => $this->activeTab,
                        'tableFilters' => $this->tableFilters,
                        'tableSearch' => $this->tableSearch
                    ]
                ))
                ->visible(fn(Category $category): bool => !$category->trashed()),
            Actions\DeleteAction::make()
                ->label('Trash')
                ->icon('heroicon-o-trash'),
            Actions\RestoreAction::make()
                ->icon(FilamentIcon::resolve('actions::restore-action') ?? 'heroicon-m-arrow-uturn-left')
                ->color('success')
                ->successRedirectUrl(fn(): string =>
                static::getResource()::getUrl(
                    'index',
                    [
                        'record' => $this->record,
                        'page' => $this->page,
                        'activeTab' => $this->activeTab,
                        'tableFilters' => $this->tableFilters,
                        'tableSearch' => $this->tableSearch
                    ]
                )),
            Actions\ForceDeleteAction::make()->icon('heroicon-o-trash'),
            Actions\Action::make('list')
                ->label('Back')
                ->color('gray')
                ->icon('heroicon-o-chevron-left')
                ->url(fn(): string =>
                static::getResource()::getUrl(
                    'index',
                    [
                        'page' => $this->page,
                        'activeTab' => $this->activeTab,
                        'tableFilters' => $this->tableFilters,
                        'tableSearch' => $this->tableSearch
                    ]
                )),
        ];
    }

    public function getTitle(): string
    {
        return __('View Banner Category');
    }

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
