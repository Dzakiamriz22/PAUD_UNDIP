<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $brand_name;
    public ?string $brand_logo;
    public ?string $brand_logo_dark;
    public string $brand_logoHeight;
    public ?string $brand_logo_square;
    public ?string $site_favicon;
    public array $site_theme;
    public ?string $login_cover_image;

    public bool $search_engine_indexing;

    public string $application_code = '';
    public string $application_owner = '';
    // public string $invoice_number_format;

    public static function group(): string
    {
        return 'general';
    }
}
