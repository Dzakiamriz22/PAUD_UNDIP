<?php

namespace App\Filament\Pages\Auth;

use App\Jobs\MailSender;
use App\Mail\ForgotPasswordMail;
use App\Settings\MailSettings;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Notifications\Auth\ResetPassword as ResetPasswordNotification;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    use HasCustomLayout;

    protected static string $view = 'filament-panels::pages.auth.password-reset.request-password-reset';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()->label('Email'),
                \Coderflex\FilamentTurnstile\Forms\Components\Turnstile::make('captcha')
                    ->hiddenLabel()
                    ->size('flexible'),
            ]);
    }

    public function request(?MailSettings $settings = null): void
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        try {
            $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
                $data,
                function (CanResetPassword $user, string $token) use ($settings): void {
                    // if (! method_exists($user, 'notify')) {
                    //     $userClass = $user::class;

                    //     throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                    // }

                    // $settings = app(MailSettings::class);
                    // $notification = new ResetPasswordNotification($token);
                    // $notification->url = Filament::getResetPasswordUrl($token, $user);

                    // $settings->loadMailSettingsToConfig();

                    // $user->notify($notification);

                    /** @var \App\Models\User $user */
                    // $user = Filament::auth()->user();

                    if ($user->hasRole('customer') && $user->roles->count() === 1) {

                        throw new Exception("We can't find a user with that email address.");
                    }

                    if (! method_exists($user, 'notify')) {
                        $userClass = $user::class;

                        throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                    }

                    // $notification = new ResetPasswordNotification($token);
                    // $notification->url = Filament::getResetPasswordUrl($token, $user);

                    if ($settings->isMailSettingsConfigured()) {

                        $settings->loadMailSettingsToConfig();

                        $mailTo = $user->email;
                        $mailData = [
                            'title' => 'Reset Password',
                            'body' => 'You are receiving this email because we received a password reset request for your account.',
                            'theme' => $settings->getEmailThemeConfig(),
                            'reset_password_url' => Filament::getResetPasswordUrl($token, $user)
                        ];
                        $mailTemplate = new ForgotPasswordMail($mailData);

                        if ($settings->queue_emails) {
                            // $user->notify($notification);
                            $queue_name = $settings->queue_name;
                            MailSender::dispatch($mailTo, $mailTemplate)->onQueue($queue_name);
                        } else {
                            // \Illuminate\Support\Facades\Notification::sendNow($user, $notification);
                            Mail::to($mailTo)->send($mailTemplate);
                        }

                        // Notification::make()
                        //     ->title(__('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resent.title'))
                        //     ->success()
                        //     ->send();
                    } else {
                        Notification::make()
                            ->title(__('resource.user.notifications.verify_warning.title'))
                            ->body(__('resource.user.notifications.verify_warning.description'))
                            ->warning()
                            ->send();
                    }
                },
            );
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            Notification::make()
                ->title(__($status))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__($status))
            ->success()
            ->send();

        $this->form->fill();
    }
}
