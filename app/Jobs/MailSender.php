<?php

namespace App\Jobs;

use App\Mail\RegistrationMail;
use App\Models\Pendaftaran;
use App\Services\PendaftaranService;
use App\Settings\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MailSender implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mailTo;
    public $mailTemplate;

    public function __construct($mailTo, $mailTemplate)
    {
        $this->mailTo = $mailTo;
        $this->mailTemplate = $mailTemplate;
    }

    public function handle(?MailSettings $settings = null)
    {
        $settings->loadMailSettingsToConfig();
        Log::info("Sending email via ", [
            'to' => $this->mailTo,
            'settings' => $settings
        ]);
        Mail::to($this->mailTo)->send($this->mailTemplate);
    }
}
