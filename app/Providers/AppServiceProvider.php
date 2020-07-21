<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Facebook\Facebook;
use Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
	    Schema::defaultStringLength(191);

        // Custom blade view directives
        Blade::directive('icon', function ($expression) {
            return "<?php echo icon($expression); ?>";
        });

    }

    /**
     * Register any applicxation services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton(Facebook::class, function ($app) {
            return new Facebook(config('facebook.config'));
        });

        
    }
}
