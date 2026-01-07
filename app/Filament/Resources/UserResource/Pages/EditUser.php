<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;
use Livewire\Attributes\Url;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    #[Url]
    public ?string $page = null;

    #[Url]
    public ?string $activeTab = null;

    #[Url]
    public ?array $tableFilters = null;

    #[Url]
    public ?string $tableSearch = null;

    // protected function getHeaderActions(): array
    // {
    //     $actions = [
    //         Actions\ActionGroup::make([
    //             Actions\EditAction::make()
    //                 ->label('Change password')
    //                 ->form([
    //                     Forms\Components\TextInput::make('password')
    //                         ->password()
    //                         ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
    //                         ->dehydrated(fn(?string $state): bool => filled($state))
    //                         ->revealable()
    //                         ->required(),
    //                     Forms\Components\TextInput::make('passwordConfirmation')
    //                         ->password()
    //                         ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
    //                         ->dehydrated(fn(?string $state): bool => filled($state))
    //                         ->revealable()
    //                         ->same('password')
    //                         ->required(),
    //                 ])
    //                 ->modalWidth(Support\Enums\MaxWidth::Medium)
    //                 ->modalHeading('Update Password')
    //                 ->modalDescription(fn($record) => $record->email)
    //                 ->modalAlignment(Alignment::Center)
    //                 ->modalCloseButton(false)
    //                 ->modalSubmitActionLabel('Submit')
    //                 ->modalCancelActionLabel('Cancel'),

    //             Actions\DeleteAction::make()
    //                 ->extraAttributes(["class" => "border-b"]),

    //             Actions\CreateAction::make()
    //                 ->label('Create new user')
    //                 ->url(fn(): string => static::$resource::getNavigationUrl() . '/create'),
    //         ])
    //         ->icon('heroicon-m-ellipsis-horizontal')
    //         ->hiddenLabel()
    //         ->button()
    //         ->tooltip('More Actions')
    //         ->color('gray')
    //     ];

    //     return $actions;
    // }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Impersonate::make()
                ->record($this->getRecord())
                ->color('warning')
                ->outlined(),
            Actions\Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->requiresConfirmation()
                ->color('warning')
                ->action(function () {
                    $this->record->update([
                        'password' => Hash::make(config('app.default_user_password')),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Password Reset')
                        ->body('The password has been reset to : ' . config('app.default_user_password'))
                        ->success()
                        ->send();
                }),
        ];
    }

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    public function getFormActions(): array
    {
        return [
            // ...parent::getFormActions(),
            parent::getSaveFormAction()
                ->label('Save Changes')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Actions\Action::make('create')
                ->label('Create Another')
                ->color('primary')
                ->icon('heroicon-o-plus-circle')
                ->authorize('create', static::getModel())
                ->url(fn(): string => static::getResource()::getUrl('create')),
            Actions\Action::make('list')
                ->label('Back')
                ->color('gray')
                ->icon('heroicon-o-chevron-left')
                // ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = ' . \Illuminate\Support\Js::from($this->previousUrl ?? static::getResource()::getUrl('index')) . ')'),
                ->url(fn(): string => static::getResource()::getUrl('index', ['page' => $this->page, 'activeTab' => $this->activeTab, 'tableFilters' => $this->tableFilters, 'tableSearch' => $this->tableSearch])),
            Actions\DeleteAction::make()->icon('heroicon-o-trash'),
        ];
    }

    // public function getTitle(): string|Htmlable
    // {
    //     $title = $this->record->fullname;
    //     $badge = $this->getBadgeStatus();

    //     return new HtmlString("
    //         <div class='flex items-center space-x-2'>
    //             <div>$title</div>
    //             $badge
    //         </div>
    //     ");
    // }

    public function getBadgeStatus(): string|Htmlable
    {
        if (empty($this->record->email_verified_at)) {
            $badge = "<span class='inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md text-danger-700 bg-danger-50 ring-1 ring-inset ring-danger-600/20'>Unverified</span>";
        } else {
            $badge = "<span class='inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md text-success-700 bg-success-50 ring-1 ring-inset ring-success-600/20'>Verified</span>";
        }

        return $badge;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->id === $this->record->id) {
            if (!$user->can('page_MyCustomProfile')) {
                abort(403);
            }
            $this->redirect(route('filament.admin.pages.my-profile'));
        }
    }
}
