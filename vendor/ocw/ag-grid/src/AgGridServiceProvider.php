<?php

namespace Ocw\AgGrid;

use Illuminate\Support\ServiceProvider;
use Ocw\AgGrid\AgGrid;

class AgGridServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('aggrid', function($app) {
            return new AgGrid();
        });
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
