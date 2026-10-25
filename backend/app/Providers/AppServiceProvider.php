<?php

namespace App\Providers;

use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use App\Policies\ClassGroupPolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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
        // Registo explícito de Policies (complementa auto-discovery)
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(ClassGroup::class, ClassGroupPolicy::class);
    }
}

