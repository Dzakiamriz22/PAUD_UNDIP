<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class Backup extends \ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'fluentui-box-arrow-up-20-o';

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }
}
