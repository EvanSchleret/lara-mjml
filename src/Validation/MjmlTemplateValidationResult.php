<?php

namespace EvanSchleret\LaraMjml\Validation;

final class MjmlTemplateValidationResult
{
    public function __construct(
        public readonly string $path,
        public readonly bool $passed,
        public readonly array $errors = [],
        public readonly ?string $exceptionMessage = null,
        public readonly array $issues = [],
    ) {}
}
