<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;

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
        Collection::macro('stdDev', function ($key = null) {
            $collection = $key ? $this->pluck($key) : $this;

            $count = $collection->count();
            if ($count === 0) return 0;

            $mean = $collection->avg();
            $sum = $collection->sum(fn($value) => pow($value - $mean, 2));

            return sqrt($sum / $count);
        });
    }
}
