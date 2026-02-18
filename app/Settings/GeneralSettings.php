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
    
    // School information
    public string $school_name = '';
    public ?string $school_address = null;
    public ?string $school_contact = null;
    public ?string $school_logo_url = null;

    public static function group(): string
    {
        return 'general';
    }
}
