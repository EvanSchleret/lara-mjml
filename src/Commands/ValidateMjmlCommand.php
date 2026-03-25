<?php

namespace EvanSchleret\LaraMjml\Commands;

use EvanSchleret\LaraMjml\Validation\MjmlTemplateValidator;
use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Mjml\ValidationLevel;

class ValidateMjmlCommand extends Command
{
    protected $signature = 'laramjml:validate
        {--path=* : File or directory to validate (relative to the application base path)}
        {--validation=strict : MJML validation level (strict, soft, skip)}';

    protected $description = 'Validate MJML Blade templates and fail for CI when validation errors are detected';

    public function __construct(
        private ConfigRepository $config,
        private MjmlTemplateValidator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $validationOption = (string) $this->option('validation');
        $validationLevel = ValidationLevel::tryFrom($validationOption);

        if (! $validationLevel) {
            $this->error('Invalid validation level. Allowed values: strict, soft, skip.');

            return self::FAILURE;
        }

        $this->configureNodePath();

        $files = $this->discoverTemplateFiles($this->resolveTargets());

        if ($files === []) {
            $this->warn('No .mjml.blade.php templates found.');

            return self::SUCCESS;
        }

        $failedCount = 0;

        foreach ($files as $filePath) {
            $contents = file_get_contents($filePath);

            if (! is_string($contents)) {
                $failedCount++;
                $this->error("FAIL {$filePath}");
                $this->line('  Unable to read file contents.');

                continue;
            }

            $result = $this->validator->validate($filePath, $contents, $validationLevel);

            if ($result->passed) {
                $this->info("OK   {$result->path}");

                continue;
            }

            $failedCount++;
            $this->error("FAIL {$result->path}");

            if (is_string($result->exceptionMessage) && $result->exceptionMessage !== '') {
                $this->line("  {$result->exceptionMessage}");
            }

            foreach ($result->errors as $error) {
                $this->line("  - {$error}");
            }
        }

        $validCount = count($files) - $failedCount;
        $this->newLine();
        $this->line('Validated '.count($files)." template(s): {$validCount} passed, {$failedCount} failed.");

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function configureNodePath(): void
    {
        $binaryPath = $this->config->get('laramjml.binary_path');

        if (is_string($binaryPath) && $binaryPath !== '') {
            putenv('MJML_NODE_PATH='.$binaryPath);
        }
    }

    private function resolveTargets(): array
    {
        $paths = $this->option('path');

        if (! is_array($paths) || $paths === []) {
            return [$this->laravel->resourcePath('views')];
        }

        $targets = [];

        foreach ($paths as $path) {
            if (! is_string($path)) {
                continue;
            }

            $normalizedPath = trim($path);

            if ($normalizedPath === '') {
                continue;
            }

            $targets[] = $this->isAbsolutePath($normalizedPath)
                ? $normalizedPath
                : $this->laravel->basePath($normalizedPath);
        }

        if ($targets === []) {
            return [$this->laravel->resourcePath('views')];
        }

        return array_values(array_unique($targets));
    }

    private function discoverTemplateFiles(array $targets): array
    {
        $files = [];

        foreach ($targets as $target) {
            if (is_file($target) && str_ends_with($target, '.mjml.blade.php')) {
                $files[] = $target;

                continue;
            }

            if (! is_dir($target)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $item) {
                if (! $item->isFile()) {
                    continue;
                }

                $pathName = $item->getPathname();

                if (! str_ends_with($pathName, '.mjml.blade.php')) {
                    continue;
                }

                $files[] = $pathName;
            }
        }

        $files = array_values(array_unique($files));
        sort($files);

        return $files;
    }

    private function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
