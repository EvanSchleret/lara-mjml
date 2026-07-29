<?php

use EvanSchleret\LaraMjml\Validation\MjmlTemplateValidator;
use Spatie\Mjml\Exceptions\CouldNotConvertMjml;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlResult;
use Spatie\Mjml\ValidationLevel;

it('returns a passing result when mjml has no validation errors', function () {
    $fakeMjml = new FakeValidatorMjml(
        fn () => new MjmlResult([
            'html' => '<html></html>',
            'json' => [],
            'errors' => [],
        ])
    );

    $validator = new MjmlTemplateValidator(fn (): Mjml => $fakeMjml);

    $result = $validator->validate('/tmp/layout.mjml.blade.php', '<mjml></mjml>', ValidationLevel::Strict);

    expect($result->path)->toBe('/tmp/layout.mjml.blade.php');
    expect($result->passed)->toBeTrue();
    expect($result->errors)->toBe([]);
    expect($result->exceptionMessage)->toBeNull();
    expect($fakeMjml->lastFilePath)->toBe('/tmp/layout.mjml.blade.php');
    expect($fakeMjml->lastValidationLevel)->toBe(ValidationLevel::Strict);
});

it('returns mjml validation errors when conversion returns errors', function () {
    $fakeMjml = new FakeValidatorMjml(
        fn () => new MjmlResult([
            'html' => '',
            'json' => [],
            'errors' => [
                [
                    'line' => 7,
                    'message' => 'Element mj-column cannot be empty',
                    'tagName' => 'mj-column',
                ],
            ],
        ])
    );

    $validator = new MjmlTemplateValidator(fn (): Mjml => $fakeMjml);

    $result = $validator->validate('/tmp/layout.mjml.blade.php', '<mjml></mjml>', ValidationLevel::Soft);

    expect($result->passed)->toBeFalse();
    expect($result->errors)->toBe([
        'Line 7: Element mj-column cannot be empty',
    ]);
    expect($result->issues[0]->toArray())->toBe([
        'line' => 7,
        'message' => 'Element mj-column cannot be empty',
        'tag' => 'mj-column',
    ]);
    expect($result->exceptionMessage)->toBeNull();
    expect($fakeMjml->lastValidationLevel)->toBe(ValidationLevel::Soft);
});

it('returns a failed result when mjml conversion throws', function () {
    $fakeMjml = new FakeValidatorMjml(
        fn () => throw CouldNotConvertMjml::make('MJML binary not found')
    );

    $validator = new MjmlTemplateValidator(fn (): Mjml => $fakeMjml);

    $result = $validator->validate('/tmp/layout.mjml.blade.php', '<mjml></mjml>', ValidationLevel::Strict);

    expect($result->passed)->toBeFalse();
    expect($result->errors)->toBe([]);
    expect($result->exceptionMessage)->toBe('MJML binary not found');
});

class FakeValidatorMjml extends Mjml
{
    public ?string $lastFilePath = null;

    public ?ValidationLevel $lastValidationLevel = null;

    public function __construct(
        private $convertHandler,
    ) {
        parent::__construct();
    }

    public function filePath(string $filePath): self
    {
        $this->lastFilePath = $filePath;

        return $this;
    }

    public function validationLevel(ValidationLevel $validationLevel): self
    {
        $this->lastValidationLevel = $validationLevel;

        return $this;
    }

    public function convert(string $mjml, array $options = []): MjmlResult
    {
        return ($this->convertHandler)($mjml, $options);
    }
}
