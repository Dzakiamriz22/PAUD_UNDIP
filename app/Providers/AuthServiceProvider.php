<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Blog\Category::class => \App\Policies\Blog\CategoryPolicy::class,
        \App\Models\Blog\Post::class => \App\Policies\Blog\PostPolicy::class,
        \BezhanSalleh\FilamentExceptions\Models\Exception::class => \App\Policies\ExceptionPolicy::class,
        \Spatie\Activitylog\Models\Activity::class => \App\Policies\ActivityPolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        // \Datlechin\FilamentMenuBuilder\Models\Menu::class => \App\Policies\MenuPolicy::class,
        \App\Models\Banner\Content::class => \App\Policies\Banner\ContentPolicy::class,
        \App\Models\Banner\Category::class => \App\Policies\Banner\CategoryPolicy::class,
        \TomatoPHP\FilamentMediaManager\Models\Media::class => \App\Policies\MediaPolicy::class,
        \TomatoPHP\FilamentMediaManager\Models\Folder::class => \App\Policies\FolderPolicy::class,
        \Rupadana\ApiService\Models\Token::class => \App\Policies\TokenPolicy::class,
        \App\Models\Approvals\BookingApproval::class => \App\Policies\Approvals\BookingApprovalPolicy::class,
        \App\Models\Voucher::class => \App\Policies\VoucherPolicy::class,
        \App\Models\Settlement::class => \App\Policies\SettlementPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\Receipt::class => \App\Policies\ReceiptPolicy::class,
        \App\Models\PaymentMethod::class => \App\Policies\PaymentMethodPolicy::class,
        \App\Models\PropertyType::class => \App\Policies\PropertyTypePolicy::class,
        \App\Models\Tax::class => \App\Policies\TaxPolicy::class,
        \App\Models\RecipientStatus::class => \App\Policies\RecipientStatusPolicy::class,
        \App\Models\VoucherUsage::class => \App\Policies\VoucherUsagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
