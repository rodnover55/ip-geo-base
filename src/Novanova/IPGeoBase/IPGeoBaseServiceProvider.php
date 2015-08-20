<?php 

namespace Novanova\IPGeoBase;

use Illuminate\Support\ServiceProvider;

/**
 * Class IPGeoBaseServiceProvider
 * @package Novanova\IPGeoBase
 */
class IPGeoBaseServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/ipgeobase.php' => config_path('package.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/../../migrations/2014_03_26_155541_create_ipgeobase_tables.php' => database_path('migrations/2014_03_26_155541_create_ipgeobase_tables.php')
        ], 'migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

}
