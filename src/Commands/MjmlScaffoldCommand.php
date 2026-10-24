<?php

namespace EvanSchleret\LaraMjml\Commands;

use EvanSchleret\LaraMjml\Support\MjmlScaffoldGenerator;
use Illuminate\Console\Command;

abstract class MjmlScaffoldCommand extends Command
{
    public function __construct(
        protected MjmlScaffoldGenerator $scaffold,
    ) {
        parent::__construct();
    }

    protected function reportFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file['written']) {
                $this->info("Created {$file['label']}: {$file['path']}");

                continue;
            }

            $this->warn("Skipped {$file['label']}: {$file['path']} already exists. Use --force to replace it.");
        }
    }

    protected function applicationBasePath(): string
    {
        return $this->laravel->basePath();
    }

    protected function applicationViewsPath(): string
    {
        return $this->laravel->resourcePath('views');
    }

    protected function applicationNamespace(): string
    {
        return method_exists($this->laravel, 'getNamespace')
            ? rtrim($this->laravel->getNamespace(), '\\')
            : 'App';
    }
}
