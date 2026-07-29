<?php

namespace EvanSchleret\LaraMjml\Views\Engines;

use Closure;
use EvanSchleret\LaraMjml\Events\MjmlRendered;
use EvanSchleret\LaraMjml\Events\MjmlRendering;
use EvanSchleret\LaraMjml\Events\MjmlRenderingFailed;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\View\Engine;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlResult;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class MJMLEngine implements Engine
{
    private static array $mjmlVersions = [];

    /**
     * @var Closure(): Mjml
     */
    private Closure $mjmlFactory;

    private Closure $mjmlVersionResolver;

    public function __construct(
        private Engine $bladeEngine,
        private ConfigRepository $config,
        ?callable $mjmlFactory = null,
        private ?EventDispatcher $events = null,
        ?callable $mjmlVersionResolver = null,
    ) {
        $this->mjmlFactory = $mjmlFactory
            ? Closure::fromCallable($mjmlFactory)
            : static fn (): Mjml => Mjml::new();

        $this->mjmlVersionResolver = $mjmlVersionResolver
            ? Closure::fromCallable($mjmlVersionResolver)
            : fn (?string $binaryPath): ?string => $this->resolveMjmlVersion($binaryPath);
    }

    public function get($path, array $data = [])
    {
        $viewPath = (string) $path;
        $startedAt = hrtime(true);
        $options = $this->mjmlOptions();
        $mjmlVersion = $this->events === null
            ? null
            : ($this->mjmlVersionResolver)($this->binaryPath());
        $validationLevel = $this->validationLevel($options);

        $this->dispatch(new MjmlRendering(
            $viewPath,
            $mjmlVersion,
            $validationLevel,
        ));

        try {
            $compiledView = $this->bladeEngine->get($path, $data);
        } catch (Throwable $exception) {
            $this->dispatchFailure($viewPath, $startedAt, $mjmlVersion, $validationLevel, 'blade', $exception);

            throw $exception;
        }

        try {
            $result = $this->configureMjml(($this->mjmlFactory)())
                ->beautify((bool) $this->config->get('laramjml.beautify', false))
                ->minify((bool) $this->config->get('laramjml.minify', true))
                ->keepComments((bool) $this->config->get('laramjml.keep_comments', false))
                ->convert($compiledView, $options);
        } catch (Throwable $exception) {
            $this->dispatchFailure($viewPath, $startedAt, $mjmlVersion, $validationLevel, 'mjml', $exception);

            throw $exception;
        }

        $this->dispatch(new MjmlRendered(
            $viewPath,
            $this->durationInMilliseconds($startedAt),
            $mjmlVersion,
            $validationLevel,
            $this->resultErrors($result),
        ));

        return $result->html();
    }

    private function configureMjml(Mjml $mjml): Mjml
    {
        $binaryPath = $this->binaryPath();

        if (is_string($binaryPath) && $binaryPath !== '') {
            putenv('MJML_NODE_PATH='.$binaryPath);
        }

        return $mjml;
    }

    private function binaryPath(): ?string
    {
        $binaryPath = $this->config->get('laramjml.binary_path');

        return is_string($binaryPath) && $binaryPath !== '' ? $binaryPath : null;
    }

    private function mjmlOptions(): array
    {
        $options = $this->config->get('laramjml.options', []);

        return is_array($options) ? $options : [];
    }

    private function validationLevel(array $options): string
    {
        $validationLevel = $options['validationLevel'] ?? 'soft';

        return is_string($validationLevel) ? $validationLevel : 'soft';
    }

    private function dispatch(object $event): void
    {
        $this->events?->dispatch($event);
    }

    private function dispatchFailure(
        string $path,
        int $startedAt,
        ?string $mjmlVersion,
        string $validationLevel,
        string $phase,
        Throwable $exception,
    ): void {
        $this->dispatch(new MjmlRenderingFailed(
            $path,
            $this->durationInMilliseconds($startedAt),
            $mjmlVersion,
            $validationLevel,
            $phase,
            $exception,
        ));
    }

    private function durationInMilliseconds(int $startedAt): float
    {
        return (hrtime(true) - $startedAt) / 1_000_000;
    }

    private function resultErrors(MjmlResult $result): array
    {
        $rawResult = $result->raw();

        return is_array($rawResult['errors'] ?? null) ? $rawResult['errors'] : [];
    }

    private function resolveMjmlVersion(?string $binaryPath): ?string
    {
        $cacheKey = $binaryPath ?? 'default';

        if (array_key_exists($cacheKey, self::$mjmlVersions)) {
            return self::$mjmlVersions[$cacheKey];
        }

        try {
            $extraDirectories = $binaryPath === null ? [] : [$binaryPath];
            $nodePath = (new ExecutableFinder)->find('node', 'node', $extraDirectories);

            if ($nodePath === null) {
                return self::$mjmlVersions[$cacheKey] = null;
            }

            $process = new Process([
                $nodePath,
                '-e',
                "process.stdout.write(require('mjml/package.json').version)",
            ]);
            $process->run();

            if (! $process->isSuccessful()) {
                return self::$mjmlVersions[$cacheKey] = null;
            }

            $version = trim($process->getOutput());

            return self::$mjmlVersions[$cacheKey] = $version !== '' ? $version : null;
        } catch (Throwable) {
            return self::$mjmlVersions[$cacheKey] = null;
        }
    }
}
