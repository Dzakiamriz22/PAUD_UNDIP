<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sites.is_maintenance', false);
        $this->migrator->add('sites.name', 'Filament Starter Kit');
        $this->migrator->add('sites.logo', null);
        $this->migrator->add('sites.tagline', 'Elevate Your Development Experience');
        $this->migrator->add('sites.description', "Transform your workflow with Filament Starter Kit — the toolkit for Filament 3 projects. Packed with enterprise-ready plugins, seamless configurations, and expert-crafted interfaces to accelerate your development from concept to production.");
        $this->migrator->add('sites.default_language', 'en');
        $this->migrator->add('sites.timezone', 'UTC');
        $this->migrator->add('sites.copyright_text', '© ' . date('Y') . ' Filament Starter Kit. All rights reserved.');
        $this->migrator->add('sites.terms_url', '/terms');
        $this->migrator->add('sites.privacy_url', '/privacy');
        $this->migrator->add('sites.cookie_policy_url', '/cookie-policy');
        $this->migrator->add('sites.custom_404_message', 'Oops! This page seems to have vanished into the digital ether. Let\'s get you back on track.');
        $this->migrator->add('sites.custom_500_message', 'We\'ve encountered an unexpected glitch. Our team has been notified and is working to restore service.');
        $this->migrator->add('sites.company_name', 'Filament Starter Kit');
        $this->migrator->add('sites.company_email', 'hello@filamentstarterkit.com');
        $this->migrator->add('sites.company_phone', '62-81-6231-3123');
        $this->migrator->add('sites.company_address', 'Innovation Tower, 101 Tech Boulevard, Digital City, 10101');
    }
};
