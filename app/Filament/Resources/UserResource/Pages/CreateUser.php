<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Settings\MailSettings;
use Exception;
use Filament\Facades\Filament;
use Filament\Actions;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    // protected function afterCreate(): void
    // {
    //     $user = $this->record;
    //     $settings = app(MailSettings::class);

    //     if (! method_exists($user, 'notify')) {
    //         $userClass = $user::class;

    //         throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
    //     }

    //     if ($settings->isMailSettingsConfigured()) {
    //         $notification = new VerifyEmail();
    //         $notification->url = Filament::getVerifyEmailUrl($user);

    //         $settings->loadMailSettingsToConfig();

    //         $user->notify($notification);


    //         Notification::make()
    //             ->title(__('resource.user.notifications.verify_sent.title'))
    //             ->success()
    //             ->send();
    //     } else {
    //         Notification::make()
    //             ->title(__('resource.user.notifications.verify_warning.title'))
    //             ->body(__('resource.user.notifications.verify_warning.description'))
    //             ->warning()
    //             ->send();
    //     }
    // }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pegawai_list')
                ->label('Daftar Pegawai')
                ->icon('fluentui-folder-people-24')
                ->slideOver()
                ->modalHeading('Cari Pegawai')
                ->modalDescription('Cari user dari database pegawai yang sudah terdaftar.')
                ->infolist(function () {
                    return [
                        \Filament\Infolists\Components\Livewire::make(
                            \App\Livewire\TableSelectPegawai::class
                        )
                    ];
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
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

    #[On('set-personal-data')]
    public function refresh($personal_data)
    {
        $this->data['firstname'] =  $personal_data['firstname'];
        $this->data['lastname'] =  $personal_data['lastname'];
        $this->data['username'] =  $personal_data['nip_anggota'];
        $this->data['email'] =  $personal_data['email'];
        $this->form->fill($this->data);
    }
}
