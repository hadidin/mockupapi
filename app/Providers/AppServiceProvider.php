<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Mockery\Generator\StringManipulation\Pass\Pass;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
//    protected $policies =[
//        'App\Model' => 'App\Policies\ModelPolicy',
//    ];

    public function boot()
    {
        //
        Schema::defaultStringLength(191);
//        $this->registerPolicies();
        Passport::routes();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
