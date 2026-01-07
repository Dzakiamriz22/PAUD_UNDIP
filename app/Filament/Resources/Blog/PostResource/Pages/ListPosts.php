<?php

namespace App\Filament\Resources\Blog\PostResource\Pages;

use App\Filament\Resources\Blog\PostResource;
use App\Filament\Resources\Blog\PostResource\Widgets\BlogPostStatsWidget;
use App\Models\Blog\Post;
use Closure;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

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

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         BlogPostStatsWidget::class,
    //     ];
    // }

    // protected function getFooterWidgets(): array
    // {
    //     return [];
    // }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Post $query */
                $query->withoutTrashed();
            }),
            'published' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Post $query */
                $query->withoutTrashed()->published();
            }),
            'trashed' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Post $query */
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

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (Post $post): ?string {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if (!$post->trashed()) {
                if ($user->hasAnyRole(['admin', config('filament-shield.super_admin.name')])) {
                    return static::getResource()::getUrl('edit', ['record' => $post, 'page' => $this->getPage(), 'activeTab' => $this->activeTab, 'tableFilters' => $this->tableFilters, 'tableSearch' => $this->tableSearch]);
                }
                return $post->created_by == Auth::user()->id ? static::getResource()::getUrl('edit', ['record' => $post, 'page' => $this->getPage(), 'activeTab' => $this->activeTab, 'tableFilters' => $this->tableFilters, 'tableSearch' => $this->tableSearch]) : null;
            }
            return null;
        };
    }
}
