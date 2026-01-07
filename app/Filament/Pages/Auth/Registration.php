<?php

namespace App\Filament\Pages\Auth;

use App\Models\Mhs;
use App\Models\Pegawai;
use App\Services\EmailVerificationService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Form;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Events\Auth\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Notifications\Notification;

use Illuminate\Database\Eloquent\Model;

class Registration extends \Filament\Pages\Auth\Register
{

    use HasCustomLayout;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                \Coderflex\FilamentTurnstile\Forms\Components\Turnstile::make('captcha')
                    ->hiddenLabel()
                    ->size('flexible'),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $password = $data['password'];
        $pegawai = Pegawai::where('email_sso', $data['email'])->first();
        if (empty($pegawai)) {
            $mhs = Mhs::where('sso_email', $data['email'])->first();
            if (empty($mhs)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.email' => 'Email tidak terdaftar',
                ]);
            } else {
                $data = [
                    'username' => $mhs->nim,
                    'email' => $mhs->sso_email,
                    'firstname' => $mhs->nama,
                    'lastname' => '',
                ];
            }
        } else {
            $data = [
                'username' => $pegawai->nip,
                'email' => $pegawai->email_sso,
                'firstname' => trim($pegawai->gelar_depan . ' ' . $pegawai->nama),
                'lastname' => $pegawai->gelar_belakang,
            ];
        }

        //  $data['password'] = config('app.default_user_password');
        $data['password'] = $password;

        // Cek suffix email untuk menentukan role
        if (\Illuminate\Support\Str::endsWith($data['email'], '@students.undip.ac.id')) {
            $role = 'mhs';
        } else {
            $role = $pegawai->jnspeg == '1' ? 'dosen' : 'tendik';
        }

        $user = $this->getUserModel()::create($data);
        $user->assignRole($role);
        return $user;
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        $emailVerificationService = app(EmailVerificationService::class);
        $sent = $emailVerificationService->sendVerificationEmail($user);

        if ($sent) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resent.title'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('resource.user.notifications.verify_warning.title'))
                ->body(__('resource.user.notifications.verify_warning.description'))
                ->warning()
                ->send();
        }
    }

    // public function getHeading(): string | Htmlable
    // {
    //     return '';
    // }

    // public function getUsernameFormComponent(): Component
    // {
    //     return TextInput::make('username')
    //         ->required()
    //         ->autocomplete()
    //         ->autofocus()
    //         ->extraInputAttributes(['tabindex' => 1]);
    // }

    // protected function getCredentialsFromFormData(array $data): array
    // {
    //     return [
    //         'username' => $data['username'],
    //         'password' => $data['password'],
    //     ];
    // }

    // protected function throwFailureValidationException(): never
    // {
    //     throw ValidationException::withMessages([
    //         'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
    //     ]);
    // }
}
