<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.stamp_image_dekan', null);
        $this->migrator->add('general.stamp_image_wd1', null);
        $this->migrator->add('general.stamp_image_wd2', null);
        $this->migrator->add('general.stamp_image_manager', null);
        $this->migrator->add('general.stamp_image_spv1', null);
        $this->migrator->add('general.stamp_image_spv2', null);
    }
};
