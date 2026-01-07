<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Closure;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ListUsers extends ListRecords
{
    use ExposesTableToWidgets;
    protected static string $resource = UserResource::class;

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
        return static::$resource::getWidgets();
    }

    public function getTabs(): array
    {
        /** @var \App\Models\User $user */
        // $user = Auth::user();
        $tabs = [
            'all' => Tab::make()->query(fn($query) => $query->withoutTrashed()),
            'admin' => Tab::make()->query(fn($query) => $query->withoutTrashed()->with('roles')->whereRelation('roles', 'name', '=', 'admin')),
            'operator' => Tab::make()->query(fn($query) => $query->withoutTrashed()->with('roles')->whereRelation('roles', 'name', '=', 'operator')),
            'trashed' => Tab::make()->icon('heroicon-o-trash')->query(fn($query) => $query->onlyTrashed()),
        ];

        // if ($user->isSuperAdmin()) {
        //     $tabs['superadmin'] = Tab::make()->query(fn ($query) => $query->with('roles')->whereRelation('roles', 'name', '=', config('filament-shield.super_admin.name')));
        // }

        return $tabs;
    }

    // protected function getTableQuery(): Builder
    // {
    //     /** @var \App\Models\User $user */
    //     $user = Auth::user();
    //     $model = (new (static::$resource::getModel()))->with('roles')->where('id', '!=', $user->id);

    //     $model = $model->whereDoesntHave('roles', function ($query) {
    //         $query->where('name', '=', config('filament-shield.super_admin.name'));
    //     });

    //     return $model;
    // }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (User $user): ?string {
            /** @var \App\Models\User $login_user */
            $login_user = Auth::user();
            if (!$user->trashed()) {
                if ($login_user->hasAnyRole(['admin', config('filament-shield.super_admin.name')])) {
                    return static::getResource()::getUrl('edit', ['record' => $user, 'page' => $this->getPage(), 'activeTab' => $this->activeTab, 'tableFilters' => $this->tableFilters, 'tableSearch' => $this->tableSearch]);
                }
                return $user->created_by == Auth::user()->id ? static::getResource()::getUrl('edit', ['record' => $user, 'page' => $this->getPage(), 'activeTab' => $this->activeTab, 'tableFilters' => $this->tableFilters, 'tableSearch' => $this->tableSearch]) : null;
            }
            return null;
        };
    }
}
