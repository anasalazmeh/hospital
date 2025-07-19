<?php

namespace App\Providers;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Models\Bed;
use App\Observers\BedObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    protected $namespace = 'App\\Http\\Controllers'; // تحديد الفضاء الاسمي للمتحكمات
        public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Bed::observe(BedObserver::class);
    }
}
