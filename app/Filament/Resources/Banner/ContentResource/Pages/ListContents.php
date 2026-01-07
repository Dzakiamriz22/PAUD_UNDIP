<?php

namespace App\Filament\Resources\Banner\ContentResource\Pages;

use App\Filament\Resources\Banner\ContentResource;
use App\Filament\Resources\Banner\ContentResource\Widgets\BannerStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

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
        return [
            BannerStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('Banner Content Management');
    }
}
