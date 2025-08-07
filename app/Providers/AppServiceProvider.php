<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Channels\OneSignalChannel;
use App\Services\OneSignalService;
use Illuminate\Support\Facades\Notification;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('onesignal', function () {
        return new OneSignalChannel(new OneSignalService());
    });
    }
}
