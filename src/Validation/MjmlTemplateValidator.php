<?php

namespace EvanSchleret\LaraMjml\Validation;

use Closure;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlError;
use Spatie\Mjml\ValidationLevel;
use Throwable;

final class MjmlTemplateValidator
{
    private Closure $mjmlFactory;

    public function __construct(?callable $mjmlFactory = null)
    {
        $this->mjmlFactory = $mjmlFactory
            ? Closure::fromCallable($mjmlFactory)
            : static fn (): Mjml => Mjml::new();
    }

    public function validate(string $path, string $contents, ValidationLevel $validationLevel): MjmlTemplateValidationResult
    {
        try {
            $result = ($this->mjmlFactory)()
                ->filePath($path)
                ->validationLevel($validationLevel)
                ->convert($contents);
        } catch (Throwable $throwable) {
            return new MjmlTemplateValidationResult(
                path: $path,
                passed: false,
                exceptionMessage: $throwable->getMessage(),
            );
        }

        $issues = array_map(
            static fn (MjmlError $error): MjmlTemplateValidationIssue => new MjmlTemplateValidationIssue(
                line: $error->line(),
                message: $error->message(),
                tagName: $error->tagName(),
            ),
            $result->errors(),
        );

        $errors = array_map(
            static fn (MjmlTemplateValidationIssue $issue): string => $issue->formattedMessage(),
            $issues,
        );

        return new MjmlTemplateValidationResult(
            path: $path,
            passed: $errors === [],
            errors: $errors,
            issues: $issues,
        );
    }
}
