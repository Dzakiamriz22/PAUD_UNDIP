<?php

namespace App\Filament\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Resources\Blog\PostCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;

class CreateCategory extends CreateRecord
{
    protected static string $resource = PostCategoryResource::class;

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    public function getTitle(): string
    {
        return __('Create Blog Category');
    }

    protected function getFormActions(): array
    {
        return [
            // ...parent::getFormActions(),
            parent::getCreateFormAction()
                ->label('Save Record')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            parent::getCancelFormAction()
                ->label('Back')
                ->icon('heroicon-o-chevron-left'),
        ];
    }
}
