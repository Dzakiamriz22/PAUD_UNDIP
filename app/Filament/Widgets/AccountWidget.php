<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AccountWidget extends \Filament\Widgets\AccountWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = -2;
    protected static bool $isLazy = false;
    protected static string $view = 'filament.widgets.account-widget';
}
