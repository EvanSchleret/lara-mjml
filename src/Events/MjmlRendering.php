<?php

namespace EvanSchleret\LaraMjml\Events;

final class MjmlRendering
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $mjmlVersion,
        public readonly string $validationLevel,
    ) {}
}
