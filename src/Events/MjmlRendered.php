<?php

namespace EvanSchleret\LaraMjml\Events;

final class MjmlRendered
{
    public function __construct(
        public readonly string $path,
        public readonly float $durationMs,
        public readonly ?string $mjmlVersion,
        public readonly string $validationLevel,
        public readonly array $errors,
    ) {}
}
