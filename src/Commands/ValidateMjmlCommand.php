<?php

namespace EvanSchleret\LaraMjml\Commands;

use EvanSchleret\LaraMjml\Validation\MjmlTemplateValidationIssue;
use EvanSchleret\LaraMjml\Validation\MjmlTemplateValidationResult;
use EvanSchleret\LaraMjml\Validation\MjmlTemplateValidator;
use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\View;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Mjml\ValidationLevel;
use Throwable;

class ValidateMjmlCommand extends Command
{
    protected $signature = 'laramjml:validate
        {--path=* : File or directory to validate (relative to the application base path)}
        {--validation=strict : MJML validation level (strict, soft, skip)}
        {--format=table : Output format (table, json, github)}
        {--render : Render Blade templates before validating MJML}
        {--data= : JSON object passed to Blade when using --render}';

    protected $description = 'Validate MJML Blade templates and fail for CI when validation errors are detected';

    public function __construct(
        private ConfigRepository $config,
        private MjmlTemplateValidator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $format = (string) $this->option('format');

        if (! in_array($format, ['table', 'json', 'github'], true)) {
            $this->error('Invalid output format. Allowed values: table, json, github.');

            return self::FAILURE;
        }

        $validationOption = (string) $this->option('validation');
        $validationLevel = ValidationLevel::tryFrom($validationOption);

        if (! $validationLevel) {
            $this->error('Invalid validation level. Allowed values: strict, soft, skip.');

            return self::FAILURE;
        }

        $data = $this->resolveData();

        if ($data === null) {
            return self::FAILURE;
        }

        $this->configureNodePath();

        $files = $this->discoverTemplateFiles($this->resolveTargets());
        $results = $this->validateFiles($files, $validationLevel, (bool) $this->option('render'), $data);

        return $this->renderResults($results, $format);
    }

    private function validateFiles(array $files, ValidationLevel $validationLevel, bool $render, array $data): array
    {
        $results = [];

        foreach ($files as $filePath) {
            try {
                $contents = $this->readTemplateContents($filePath, $render, $data);
            } catch (Throwable $throwable) {
                $results[] = new MjmlTemplateValidationResult(
                    path: $filePath,
                    passed: false,
                    exceptionMessage: $throwable->getMessage(),
                );

                continue;
            }

            if (! is_string($contents)) {
                $results[] = new MjmlTemplateValidationResult(
                    path: $filePath,
                    passed: false,
                    exceptionMessage: $render
                        ? 'Unable to render file contents.'
                        : 'Unable to read file contents.',
                );

                continue;
            }

            $results[] = $this->validator->validate($filePath, $contents, $validationLevel);
        }

        return $results;
    }

    private function readTemplateContents(string $filePath, bool $render, array $data): ?string
    {
        if (! $render) {
            $contents = file_get_contents($filePath);

            return is_string($contents) ? $contents : null;
        }

        return View::getEngineResolver()->resolve('blade')->get($filePath, $data);
    }

    private function renderResults(array $results, string $format): int
    {
        $failedCount = count(array_filter(
            $results,
            static fn (MjmlTemplateValidationResult $result): bool => ! $result->passed,
        ));
        $validCount = count($results) - $failedCount;

        return match ($format) {
            'json' => $this->renderJsonResults($results, $validCount, $failedCount),
            'github' => $this->renderGithubResults($results, $validCount, $failedCount),
            default => $this->renderTableResults($results, $validCount, $failedCount),
        };
    }

    private function renderTableResults(array $results, int $validCount, int $failedCount): int
    {
        if ($results === []) {
            $this->warn('No .mjml.blade.php templates found.');

            return self::SUCCESS;
        }

        foreach ($results as $result) {
            if ($result->passed) {
                $this->info("OK   {$result->path}");

                continue;
            }

            $this->error("FAIL {$result->path}");
            $this->renderTableErrors($result);
        }

        $this->newLine();
        $this->line('Validated '.count($results)." template(s): {$validCount} passed, {$failedCount} failed.");

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function renderTableErrors(MjmlTemplateValidationResult $result): void
    {
        if (is_string($result->exceptionMessage) && $result->exceptionMessage !== '') {
            $this->line("  {$result->exceptionMessage}");
        }

        foreach ($result->errors as $error) {
            $this->line("  - {$error}");
        }
    }

    private function renderJsonResults(array $results, int $validCount, int $failedCount): int
    {
        try {
            $payload = [
                'valid' => $failedCount === 0,
                'files' => array_map(
                    static fn (MjmlTemplateValidationResult $result): array => [
                        'path' => $result->path,
                        'passed' => $result->passed,
                        'errors' => $result->errors,
                        'issues' => array_map(
                            static fn (MjmlTemplateValidationIssue $issue): array => $issue->toArray(),
                            $result->issues,
                        ),
                        'exception' => $result->exceptionMessage,
                    ],
                    $results,
                ),
                'summary' => [
                    'total' => count($results),
                    'passed' => $validCount,
                    'failed' => $failedCount,
                ],
            ];

            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function renderGithubResults(array $results, int $validCount, int $failedCount): int
    {
        if ($results === []) {
            $this->line('::notice::No .mjml.blade.php templates found.');

            return self::SUCCESS;
        }

        foreach ($results as $result) {
            if ($result->passed) {
                $this->line('::notice file='.$this->escapeGithubProperty($result->path).'::MJML validation passed');

                continue;
            }

            if (is_string($result->exceptionMessage) && $result->exceptionMessage !== '') {
                $this->line(
                    '::error file='.$this->escapeGithubProperty($result->path).'::'.$this->escapeGithubMessage($result->exceptionMessage)
                );
            }

            foreach ($result->issues as $issue) {
                $message = $issue->tagName !== ''
                    ? "[{$issue->tagName}] {$issue->message}"
                    : $issue->message;

                $this->line(
                    '::error file='.$this->escapeGithubProperty($result->path).
                    ',line='.$issue->line.'::'.$this->escapeGithubMessage($message)
                );
            }
        }

        $this->line('::notice::Validated '.count($results)." template(s): {$validCount} passed, {$failedCount} failed.");

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function escapeGithubProperty(string $value): string
    {
        return str_replace(
            ['%', "\r", "\n", ',', ':'],
            ['%25', '%0D', '%0A', '%2C', '%3A'],
            $value,
        );
    }

    private function escapeGithubMessage(string $value): string
    {
        return str_replace(
            ['%', "\r", "\n"],
            ['%25', '%0D', '%0A'],
            $value,
        );
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

        if (! is_array($data)) {
            $this->error('The --data option must contain a JSON object.');

            return null;
        }

        return $data;
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

        return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
