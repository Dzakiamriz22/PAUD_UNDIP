<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ApplicationInfo extends Widget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = -2;
    protected static bool $isLazy = false;
    protected static string $view = 'filament.widgets.application-info';
}
