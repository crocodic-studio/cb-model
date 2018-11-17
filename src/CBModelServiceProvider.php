<?php
namespace Crocodicstudio\Cbmodel;

use Crocodicstudio\Cbmodel\Core\CBModelTemporary;
use Illuminate\Support\ServiceProvider;

class CBModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */

    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('CBModel', function () {
            return true;
        });

        if($this->app->runningInConsole()) {
            $this->commands([
                '\Crocodicstudio\Cbmodel\Commands\MakeModel'
            ]);
        }

        $this->app->singleton('CBModelTemporary',CBModelTemporary::class);

    }

}
