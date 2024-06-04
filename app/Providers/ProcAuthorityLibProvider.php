<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ProcAuthorityLibProvider extends ServiceProvider
{
    /**
     * Register services.
     *p
     * @return void
     */
    public function register()
    {
        $this->app->bind('procAuthority', 'App\Libs\ProcAuthorityLib');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
