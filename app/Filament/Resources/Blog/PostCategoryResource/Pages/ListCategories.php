<?php

namespace App\Filament\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Resources\Blog\PostCategoryResource;
use App\Filament\Resources\Blog\PostCategoryResource\Widgets\PostCategoryDistributionWidget;
use App\Filament\Resources\Blog\PostResource;
use App\Models\Blog\Category;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Filters\Filter;
use Livewire\Attributes\Url;

class ListCategories extends ListRecords
{
    protected static string $resource = PostCategoryResource::class;

    #[Url]
    public ?string $activeTab = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Record')
                ->color('primary')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // PostCategoryDistributionWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return __('Blog Categories');
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Category $query */
                $query->withoutTrashed();
            }),
            'trashed' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Category $query */
                $query->onlyTrashed();
            })->icon('heroicon-o-trash'),
        ];

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->deselectAllTableRecords();
    }
}
