<?php

namespace Compassites\EnvironmentHelper;

use Illuminate\Support\ServiceProvider;

class EnvironmentHelperServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton('Compassites\EnvironmentHelper\EnvironmentHelper', function($app) {
            return new EnvironmentHelper($app['GlobalSettingsEnvironment']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
         return ['Compassites\EnvironmentHelper\EnvironmentHelper'];
    }

}
