<?php

namespace App\Providers;

use App\Mail\Transport\PHPMailerTransport;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Mail::extend('phpmailer', fn (array $config) => new PHPMailerTransport($config));

        $this->applyCompanyTimezone();
    }

    /**
     * Swallows any DB error - the install wizard and fresh/unconfigured
     * environments run requests before company_settings exists.
     */
    private function applyCompanyTimezone(): void
    {
        try {
            if (Schema::hasTable('company_settings')) {
                $timezone = CompanySetting::current()->timezone;
                date_default_timezone_set($timezone);
                config(['app.timezone' => $timezone]);
            }
        } catch (\Throwable) {
            //
        }
    }
}
