<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.school_name', 'PAUD Permata UNDIP');
        $this->migrator->add('general.school_address', 'XC2W+89G, Jl. Prof. Soedarto, Tembalang, Kec. Tembalang, Kota Semarang, Jawa Tengah 50275');
        $this->migrator->add('general.school_contact', 'Telp: (024) 7460012');
        $this->migrator->add('general.school_logo_url', null);
    }
};
