<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.application_code', 'F1.01');
        $this->migrator->add('general.application_owner', 'Filament Starter Kit');
    }
};
