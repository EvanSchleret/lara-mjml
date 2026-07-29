<?php

namespace EvanSchleret\LaraMjml\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spatie\Mjml\Mjml;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class MjmlDoctorCommand extends Command
{
    protected $signature = 'laramjml:doctor';

    protected $description = 'Check the LaraMJML runtime and configuration';

    private Closure $nodePathResolver;

    private Closure $processRunner;

    private Closure $mjmlFactory;

    public function __construct(
        private ConfigRepository $config,
        ?callable $nodePathResolver = null,
        ?callable $processRunner = null,
        ?callable $mjmlFactory = null,
    ) {
        parent::__construct();

        $this->nodePathResolver = $nodePathResolver
            ? Closure::fromCallable($nodePathResolver)
            : fn (?string $configuredPath): ?string => $this->findNodePath($configuredPath);

        $this->processRunner = $processRunner
            ? Closure::fromCallable($processRunner)
            : fn (array $command, string $workingDirectory): array => $this->runProcess($command, $workingDirectory);

        $this->mjmlFactory = $mjmlFactory
            ? Closure::fromCallable($mjmlFactory)
            : static fn (): Mjml => Mjml::new();
    }

    public function handle(): int
    {
        $this->info('LaraMJML doctor');
        $this->newLine();

        $configuredPath = $this->configuredNodePath();
        $nodePath = ($this->nodePathResolver)($configuredPath);

        if (! is_string($nodePath) || $nodePath === '') {
            $this->error('FAIL Node.js is not available.');
            $this->line('  Install Node.js or configure laramjml.binary_path.');

            return self::FAILURE;
        }

        $nodeVersion = ($this->processRunner)([$nodePath, '--version'], $this->applicationBasePath());

        if (! $nodeVersion['successful']) {
            $this->error('FAIL Node.js could not be executed.');
            $this->line('  '.$this->processError($nodeVersion));

            return self::FAILURE;
        }

        $this->info('OK   Node.js '.($nodeVersion['output'] ?: 'version unavailable'));
        $this->line('     '.$nodePath);

        $mjmlVersion = ($this->processRunner)(
            [$nodePath, '-e', "process.stdout.write(require('mjml/package.json').version)"],
            $this->applicationBasePath(),
        );

        if (! $mjmlVersion['successful']) {
            $this->error('FAIL MJML package could not be resolved.');
            $this->line('  '.$this->processError($mjmlVersion));

            return self::FAILURE;
        }

        $this->info('OK   MJML '.($mjmlVersion['output'] ?: 'version unavailable'));

        $conversionError = $this->checkConversion();

        if (is_string($conversionError)) {
            $this->error('FAIL MJML conversion failed.');
            $this->line('  '.$conversionError);

            return self::FAILURE;
        }

        $this->info('OK   MJML conversion');
        $this->newLine();
        $this->info('LaraMJML is ready.');

        return self::SUCCESS;
    }

    private function configuredNodePath(): ?string
    {
        $configuredPath = $this->config->get('laramjml.binary_path');

        if (is_string($configuredPath) && $configuredPath !== '') {
            return $configuredPath;
        }

        $environmentPath = getenv('MJML_NODE_PATH');

        return is_string($environmentPath) && $environmentPath !== ''
            ? $environmentPath
            : null;
    }

    private function findNodePath(?string $configuredPath): ?string
    {
        $extraDirectories = [
            '/usr/local/bin',
            '/opt/homebrew/bin',
        ];

        if (is_string($configuredPath) && $configuredPath !== '') {
            array_unshift($extraDirectories, $configuredPath);
        }

        return (new ExecutableFinder)->find('node', 'node', $extraDirectories);
    }

    private function checkConversion(): ?string
    {
        $previousNodePath = getenv('MJML_NODE_PATH');
        $configuredPath = $this->config->get('laramjml.binary_path');

        if (is_string($configuredPath) && $configuredPath !== '') {
            putenv('MJML_NODE_PATH='.$configuredPath);
        }

        try {
            $options = $this->config->get('laramjml.options', []);

            if (! is_array($options)) {
                $options = [];
            }

            $result = ($this->mjmlFactory)()
                ->filePath(__FILE__)
                ->beautify((bool) $this->config->get('laramjml.beautify', false))
                ->minify((bool) $this->config->get('laramjml.minify', true))
                ->keepComments((bool) $this->config->get('laramjml.keep_comments', false))
                ->convert('<mjml><mj-body><mj-text>LaraMJML doctor</mj-text></mj-body></mjml>', $options);

            return $result->html() !== '' ? null : 'MJML returned an empty HTML document.';
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        } finally {
            if ($previousNodePath === false) {
                putenv('MJML_NODE_PATH');
            } else {
                putenv('MJML_NODE_PATH='.$previousNodePath);
            }
        }
    }

    private function applicationBasePath(): string
    {
        if (function_exists('base_path')) {
            return base_path();
        }

        return getcwd() ?: '.';
    }

    private function runProcess(array $command, string $workingDirectory): array
    {
        $process = new Process($command, $workingDirectory);
        $process->run();

        return [
            'successful' => $process->isSuccessful(),
            'output' => trim($process->getOutput()),
            'error' => trim($process->getErrorOutput()),
        ];
    }

    private function processError(array $result): string
    {
        return $result['error'] !== '' ? $result['error'] : ($result['output'] ?: 'Unknown process error.');
    }
}
