<?php

namespace EvanSchleret\LaraMjml\Commands;

class MjmlInstallCommand extends MjmlScaffoldCommand
{
    protected $signature = 'laramjml:install
        {--without-examples : Do not generate the example Mailable and view}
        {--force : Replace existing generated files}';

    protected $description = 'Install LaraMJML configuration, layout and examples';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->info('Installing LaraMJML');
        $this->newLine();

        $this->reportFiles([
            $this->scaffold->writeConfig($this->laravel->configPath('laramjml.php'), $force),
            $this->scaffold->writeLayout($this->applicationViewsPath(), $force),
        ]);

        if (! $this->option('without-examples')) {
            $this->reportFiles($this->scaffold->writeMail(
                $this->applicationBasePath(),
                $this->applicationNamespace(),
                'ExampleMail',
                'emails.example',
                $force,
            ));
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line('  php artisan laramjml:doctor');
        $this->line('  php artisan laramjml:preview emails.example --data=\'{"name":"John"}\'');
        $this->line('  php artisan laramjml:validate');

        return self::SUCCESS;
    }
}
