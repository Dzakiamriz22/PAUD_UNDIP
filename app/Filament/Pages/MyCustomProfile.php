<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyCustomProfile extends \Jeffgreco13\FilamentBreezy\Pages\MyProfilePage
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'fluentui-share-screen-person-p-16-o';
    protected static ?string $navigationGroup = 'Access';

    public static function getNavigationGroup(): ?string
    {
        return 'Access';
    }
}
