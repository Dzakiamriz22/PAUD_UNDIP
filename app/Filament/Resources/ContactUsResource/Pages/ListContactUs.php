<?php

namespace App\Filament\Resources\ContactUsResource\Pages;

use App\Filament\Resources\ContactUsResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListContactUs extends ListRecords
{
    protected static string $resource = ContactUsResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\ContactUs $query */
                $query->withoutTrashed();
            }),
            'new' => Tab::make()->modifyQueryUsing(function (Builder $query) { 
                /** @var \App\Models\ContactUs $query */
                $query->where('status', 'new')->withoutTrashed();
            }),
            'trashed' => Tab::make()->modifyQueryUsing(function (Builder $query) { 
                /** @var \App\Models\ContactUs $query */
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
