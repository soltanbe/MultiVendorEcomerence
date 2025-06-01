<?php

namespace App\Providers;

use App\Services\Discounts\CategoryDiscountRule;
use App\Services\Discounts\LoyaltyCustomerDiscountRule;
use App\Services\Discounts\QuantityDiscountRule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
        $this->app->bind('discount.rules', function () {
            return [
                new CategoryDiscountRule(),
                new LoyaltyCustomerDiscountRule(),
                new QuantityDiscountRule(),
            ];
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
