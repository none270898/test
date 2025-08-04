<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Portfolio;
use App\Models\PriceAlert;
use App\Policies\PortfolioPolicy;
use App\Policies\PriceAlertPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Gate::policy(Portfolio::class, PortfolioPolicy::class);
        Gate::policy(PriceAlert::class, PriceAlertPolicy::class);
    }
}