<?php

namespace App\Filament\Pages\Auth;

use App\Services\EmailVerificationService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt;

class EmailVerification extends EmailVerificationPrompt
{

    /**
     * @var view-string
     */
    // protected static string $view = 'filament-panels::pages.auth.email-verification.email-verification-prompt';
    protected static string $view = 'livewire.email-verification-prompt';

    public function resendNotificationAction(): Action
    {
        return Action::make('resendNotification')
            ->color('danger')
            ->button()
            // ->label(__('filament-panels::pages/auth/email-verification/email-verification-prompt.actions.resend_notification.label') . '.')
            ->label(__('resource.user.email-verification.email-verification-prompt.actions.resend_notification.label'))
            ->action(function (): void {
                try {
                    $this->rateLimit(2);
                } catch (TooManyRequestsException $exception) {
                    Notification::make()
                        ->title(__('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resend_throttled.title', [
                            'seconds' => $exception->secondsUntilAvailable,
                            'minutes' => ceil($exception->secondsUntilAvailable / 60),
                        ]))
                        ->body(array_key_exists('body', __('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resend_throttled') ?: []) ? __('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resend_throttled.body', [
                            'seconds' => $exception->secondsUntilAvailable,
                            'minutes' => ceil($exception->secondsUntilAvailable / 60),
                        ]) : null)
                        ->danger()
                        ->send();

                    return;
                }

                /** @var \App\Models\User $user */
                $user = Filament::auth()->user();

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
            });
    }

    public function getTitle(): string | Htmlable
    {
        return __('filament-panels::pages/auth/email-verification/email-verification-prompt.title');
    }

    public function getHeading(): string | Htmlable
    {
        return __('filament-panels::pages/auth/email-verification/email-verification-prompt.heading');
    }
}
