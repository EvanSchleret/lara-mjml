<?php

namespace EvanSchleret\LaraMjml\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Spatie\Mjml\Mjml;

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

        Blade::extend(function ($view) {
            if (stripos($view, '<mjml>') !== false) {
                return Mjml::new()
                    ->beautify(Config::get('laramjml.beautify'))
                    ->minify(Config::get('laramjml.minify'))
                    ->keepComments(Config::get('laramjml.keep_comments'))
                    ->workingDirectory(Config::get('laramjml.working_directory'))
                    ->convert($view, ...Config::get('laramjml.options'))
                    ->html();
            }

            return $view;
        });
    }

    /**
     * Merge the configuration.
     * @return void
     */
    private function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/laramjml.php', 'laramjml'
        );
    }

    /**
     * Publish the configuration.
     * @return void
     */
    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/laramjml.php' => config_path('laramjml.php'),
        ]);
    }
}
