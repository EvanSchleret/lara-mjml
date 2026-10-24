<?php

namespace EvanSchleret\LaraMjml\Support;

use Illuminate\Filesystem\Filesystem;

final class StubWriter
{
    public function __construct(
        private Filesystem $files,
    ) {}

    public function writeStub(
        string $stub,
        string $target,
        array $replacements = [],
        bool $force = false,
    ): bool {
        $contents = $this->files->get($this->stubPath($stub));

        if ($replacements !== []) {
            $contents = strtr($contents, $replacements);
        }

        return $this->write($target, $contents, $force);
    }

    public function writeFile(string $source, string $target, bool $force = false): bool
    {
        return $this->write($target, $this->files->get($source), $force);
    }

    private function write(string $target, string $contents, bool $force): bool
    {
        if ($this->files->exists($target) && ! $force) {
            return false;
        }

        $directory = dirname($target);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($target, $contents);

        return true;
    }

    private function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2).'/stubs/'.$stub;
    }
}
