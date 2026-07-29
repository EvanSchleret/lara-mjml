<?php

namespace EvanSchleret\LaraMjml\Events;

use Throwable;

final class MjmlRenderingFailed
{
    public function __construct(
        public readonly string $path,
        public readonly float $durationMs,
        public readonly ?string $mjmlVersion,
        public readonly string $validationLevel,
        public readonly string $phase,
        public readonly Throwable $exception,
    ) {}
}
