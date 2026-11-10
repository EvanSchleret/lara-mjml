<?php

use EvanSchleret\LaraMjml\Providers\LaraMjmlServiceProvider;
use Orchestra\Testbench\TestCase;

class LaraMjmlIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaraMjmlServiceProvider::class];
    }

    public function test_it_renders_an_mjml_blade_view_in_laravel(): void
    {
        putenv('MJML_NODE_PATH');

        $this->app['view']->addLocation(__DIR__.'/fixtures');

        $html = view('integration', ['name' => 'Ada'])->render();

        $this->assertStringContainsString('<html', strtolower($html));
        $this->assertStringContainsString('Hello Ada', $html);
    }

    public function test_the_doctor_command_checks_the_installed_node_and_mjml_runtime(): void
    {
        putenv('MJML_NODE_PATH');

        $this->artisan('laramjml:doctor')->assertExitCode(0);
    }
}
