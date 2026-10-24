<?php

namespace EvanSchleret\LaraMjml\Commands;

use Closure;
use EvanSchleret\LaraMjml\Views\Engines\MJMLEngine;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\View;
use JsonException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class MjmlPreviewCommand extends Command
{
    protected $signature = 'laramjml:preview
        {view : The view name to preview}
        {--data= : JSON object passed to Blade}
        {--output= : HTML output path}
        {--no-open : Do not open the generated HTML in a browser}';

    protected $description = 'Render an MJML view and open a local HTML preview';

    private Closure $renderer;

    private Closure $opener;

    public function __construct(
        private ConfigRepository $config,
        private Filesystem $files,
        ?callable $renderer = null,
        ?callable $opener = null,
    ) {
        parent::__construct();

        $this->renderer = $renderer
            ? Closure::fromCallable($renderer)
            : fn (string $view, array $data): string => $this->renderView($view, $data);
        $this->opener = $opener
            ? Closure::fromCallable($opener)
            : fn (string $path): bool => $this->openInBrowser($path);
    }

    public function handle(): int
    {
        $data = $this->resolveData();

        if ($data === null) {
            return self::FAILURE;
        }

        try {
            $html = ($this->renderer)((string) $this->argument('view'), $data);
            $outputPath = $this->resolveOutputPath();

            $outputDirectory = dirname($outputPath);

            if (! $this->files->isDirectory($outputDirectory)) {
                $this->files->makeDirectory($outputDirectory, 0755, true);
            }

            if ($this->files->put($outputPath, $html) === false) {
                $this->error("Unable to write preview to {$outputPath}.");

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            $this->error('Preview failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Preview generated: {$outputPath}");

        if ($this->option('no-open')) {
            $this->line('Browser opening skipped.');

            return self::SUCCESS;
        }

        try {
            $opened = ($this->opener)($outputPath);
        } catch (Throwable) {
            $opened = false;
        }

        if ($opened) {
            $this->line('Preview opened in the browser.');
        } else {
            $this->warn('Unable to open a browser automatically. Open the generated file manually.');
        }

        return self::SUCCESS;
    }

    private function resolveData(): ?array
    {
        $dataOption = $this->option('data');

        if (! is_string($dataOption) || trim($dataOption) === '') {
            return [];
        }

        try {
            $data = json_decode($dataOption, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('Invalid JSON passed to --data: '.$exception->getMessage());

            return null;
        }

        if (! is_array($data) || ($data !== [] && array_is_list($data))) {
            $this->error('The --data option must contain a JSON object.');

            return null;
        }

        return $data;
    }

    private function resolveOutputPath(): string
    {
        $output = $this->option('output');

        if (is_string($output) && trim($output) !== '') {
            $output = trim($output);

            return $this->isAbsolutePath($output)
                ? $output
                : $this->laravel->basePath($output);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'lara-mjml-preview-');

        if (! is_string($temporaryPath)) {
            throw new \RuntimeException('Unable to create a temporary preview file.');
        }

        $this->files->delete($temporaryPath);

        return $temporaryPath.'.html';
    }

    private function renderView(string $view, array $data): string
    {
        $viewFactory = View::getFacadeRoot();

        if ($viewFactory === null) {
            throw new \RuntimeException('The Laravel view factory is not available.');
        }

        $viewPath = $viewFactory->getFinder()->find($view);
        $events = function_exists('app') && app()->bound('events') ? app('events') : null;
        $engine = new MJMLEngine(
            $viewFactory->getEngineResolver()->resolve('blade'),
            $this->config,
            null,
            $events,
        );

        return $engine->get($viewPath, $data);
    }

    private function openInBrowser(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            Process::fromShellCommandline('start "" '.escapeshellarg($path))->start();

            return true;
        }

        $command = PHP_OS_FAMILY === 'Darwin'
            ? (new ExecutableFinder)->find('open')
            : (new ExecutableFinder)->find('xdg-open');

        if ($command === null) {
            return false;
        }

        (new Process([$command, $path]))->start();

        return true;
    }

    private function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
