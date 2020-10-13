<?php

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        Passport::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Ide for developer
        if ($this->app->environment() !== 'production') {
            $this->app->register(IdeHelperServiceProvider::class);
        }
        if (!$this->app->runningInConsole()) {
            $themes = 'default';
            view()->share('app_site_title', config('app.name'));
            view()->share('app_site_theme', $themes);
            $viewThemes = 'frontend.themes.' . $themes;
            view()->share('view_themes', $viewThemes);
        }
    }
}
