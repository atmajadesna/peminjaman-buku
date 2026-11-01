<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Support\Facades\View;
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
        // Sidebar counters. Kept in one composer so every authenticated page
        // shows the same numbers without each controller re-querying them.
        View::composer('layouts.app', function ($view) {
            $view->with([
                'navBookCount' => Book::query()->count(),
                'navActiveLoanCount' => Loan::query()->active()->count(),
                'navOverdueCount' => Loan::query()->overdue()->count(),
            ]);
        });
    }
}
