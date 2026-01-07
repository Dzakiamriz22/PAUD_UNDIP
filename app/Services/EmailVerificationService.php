<?php

namespace App\Services;

use App\Jobs\MailSender;
use App\Mail\VerifyUserMail;
use App\Settings\MailSettings;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Filament\Facades\Filament;

class EmailVerificationService
{
    /**
     * Send email verification notification to user
     *
     * @param Model|MustVerifyEmail $user
     * @param string|null $verifyUrl Custom verification URL. If null, will use Filament's default verification URL
     * @return bool
     */
    public function sendVerificationEmail(Model $user, ?string $verifyUrl = null): bool
    {
        if (! $user instanceof MustVerifyEmail) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return false;
        }

        if (! method_exists($user, 'notify')) {
            return false;
        }

        $settings = app(MailSettings::class);

        if (!$settings->isMailSettingsConfigured()) {
            return false;
        }

        $settings->loadMailSettingsToConfig();

        // Use custom URL if provided, otherwise use Filament's default verification URL
        $verificationUrl = $verifyUrl ?? Filament::getVerifyEmailUrl($user);

        $mailTo = $user->email;
        $mailData = [
            'title' => 'Verify Email Address',
            'body' => 'Before you can fully access your account, you must verify your email address. Please click the button below to verify your email.',
            'theme' => $settings->getEmailThemeConfig(),
            'verify_url' => $verificationUrl
        ];
        $mailTemplate = new VerifyUserMail($mailData);

        if ($settings->queue_emails) {
            $queue_name = $settings->queue_name;
            MailSender::dispatch($mailTo, $mailTemplate)->onQueue($queue_name);
        } else {
            Mail::to($mailTo)->send($mailTemplate);
        }

        return true;
    }
}
