<?php

namespace EvanSchleret\LaraMjml\Providers;

use EvanSchleret\LaraMjml\Views\Engines\MJMLEngine;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class LaraMjmlServiceProvider extends ServiceProvider
{
    /**
     * Register LaraMjml service.
     */
    public function register(): void
    {
        $this->mergeConfig();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/laramjml.php' => config_path('laramjml.php'),
        ]);

        View::getEngineResolver()->register('mjml', function () {
            return new MJMLEngine;
        });

        View::addExtension('mjml.blade.php', 'mjml');
    }

    /**
     * Merge the configuration.
     */
    private function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/laramjml.php', 'laramjml'
        );
    }

    /**
     * Publish the configuration.
     */
    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/laramjml.php' => config_path('laramjml.php'),
        ]);
    }
}
