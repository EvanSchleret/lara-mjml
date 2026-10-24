<?php

namespace EvanSchleret\LaraMjml\Commands;

use Throwable;

class MakeMjmlNotificationCommand extends MjmlScaffoldCommand
{
    protected $signature = 'laramjml:make:notification
        {name : The name of the Notification class}
        {--view= : The view name without the .mjml.blade.php extension}
        {--force : Replace existing generated files}';

    protected $description = 'Generate an MJML Notification and view';

    public function handle(): int
    {
        try {
            $force = (bool) $this->option('force');

            $this->reportFiles([
                $this->scaffold->writeLayout($this->applicationViewsPath(), $force),
            ]);
            $this->reportFiles($this->scaffold->writeNotification(
                $this->applicationBasePath(),
                $this->applicationNamespace(),
                (string) $this->argument('name'),
                $this->option('view'),
                $force,
            ));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
