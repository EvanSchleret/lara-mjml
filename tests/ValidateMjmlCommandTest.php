<?php

use EvanSchleret\LaraMjml\Commands\ValidateMjmlCommand;
use EvanSchleret\LaraMjml\Validation\MjmlTemplateValidator;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlResult;
use Symfony\Component\Console\Tester\CommandTester;

function validationCommandConfig(): Repository
{
    return new Repository([
        'laramjml' => [
            'binary_path' => null,
        ],
    ]);
}

function validationCommandTester(string $path): CommandTester
{
    $validator = new MjmlTemplateValidator(fn (): Mjml => new FakeCommandValidationMjml);
    $command = new ValidateMjmlCommand(validationCommandConfig(), $validator);
    $command->setLaravel(new ValidationCommandTestApplication);

    $tester = new CommandTester($command);
    $tester->execute([
        '--path' => [$path],
    ]);

    return $tester;
}

function validationTemplatePath(): string
{
    $temporaryPath = tempnam(sys_get_temp_dir(), 'lara-mjml-');
    unlink($temporaryPath);

    $path = $temporaryPath.'.mjml.blade.php';
    file_put_contents($path, '<mjml></mjml>');

    return $path;
}

it('keeps the existing table output as the default format', function () {
    $path = validationTemplatePath();
    $tester = validationCommandTester($path);

    expect($tester->getStatusCode())->toBe(1);
    expect($tester->getDisplay())->toContain("FAIL {$path}");
    expect($tester->getDisplay())->toContain('Validated 1 template(s): 0 passed, 1 failed.');

    unlink($path);
});

it('returns structured JSON validation output', function () {
    $path = validationTemplatePath();
    $tester = validationCommandTester($path);
    $tester->execute([
        '--path' => [$path],
        '--format' => 'json',
    ]);

    $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

    expect($tester->getStatusCode())->toBe(1);
    expect($payload['valid'])->toBeFalse();
    expect($payload['summary'])->toBe([
        'total' => 1,
        'passed' => 0,
        'failed' => 1,
    ]);
    expect($payload['files'][0]['issues'][0])->toBe([
        'line' => 7,
        'message' => 'Element mj-column cannot be empty',
        'tag' => 'mj-column',
    ]);

    unlink($path);
});

it('returns GitHub workflow annotations', function () {
    $path = validationTemplatePath();
    $tester = validationCommandTester($path);
    $tester->execute([
        '--path' => [$path],
        '--format' => 'github',
    ]);

    expect($tester->getStatusCode())->toBe(1);
    expect($tester->getDisplay())->toContain(
        "::error file={$path},line=7::[mj-column] Element mj-column cannot be empty"
    );
    expect($tester->getDisplay())->toContain('::notice::Validated 1 template(s): 0 passed, 1 failed.');

    unlink($path);
});

it('rejects invalid JSON data', function () {
    $path = validationTemplatePath();
    $validator = new MjmlTemplateValidator(fn (): Mjml => new FakeCommandValidationMjml);
    $command = new ValidateMjmlCommand(validationCommandConfig(), $validator);
    $command->setLaravel(new ValidationCommandTestApplication);
    $tester = new CommandTester($command);

    expect($tester->execute([
        '--path' => [$path],
        '--render' => true,
        '--data' => '{invalid-json}',
    ]))->toBe(1);
    expect($tester->getDisplay())->toContain('Invalid JSON passed to --data');

    unlink($path);
});

it('renders Blade before validating when requested', function () {
    $path = validationTemplatePath();
    $application = new ValidationCommandTestApplication;
    $application->instance('view', new FakeCommandViewFactory);
    Facade::setFacadeApplication($application);

    $validator = new MjmlTemplateValidator(fn (): Mjml => new FakeCommandValidationMjml);
    $command = new ValidateMjmlCommand(validationCommandConfig(), $validator);
    $command->setLaravel($application);
    $tester = new CommandTester($command);

    try {
        expect($tester->execute([
            '--path' => [$path],
            '--render' => true,
            '--data' => '{"name":"Ada"}',
        ]))->toBe(1);
        expect(FakeCommandValidationMjml::$lastMjml)->toContain('Ada');
    } finally {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        unlink($path);
    }
});

class ValidationCommandTestApplication extends Container
{
    public function runningUnitTests(): bool
    {
        return true;
    }
}

class FakeCommandValidationMjml extends Mjml
{
    public static ?string $lastMjml = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function convert(string $mjml, array $options = []): MjmlResult
    {
        self::$lastMjml = $mjml;

        return new MjmlResult([
            'html' => '',
            'json' => [],
            'errors' => [
                [
                    'line' => 7,
                    'message' => 'Element mj-column cannot be empty',
                    'tagName' => 'mj-column',
                ],
            ],
        ]);
    }
}

class FakeCommandViewFactory
{
    public function getEngineResolver(): FakeCommandEngineResolver
    {
        return new FakeCommandEngineResolver;
    }
}

class FakeCommandEngineResolver
{
    public function resolve(string $engine): FakeCommandBladeEngine
    {
        return new FakeCommandBladeEngine;
    }
}

class FakeCommandBladeEngine
{
    public function get(string $path, array $data = []): string
    {
        return '<mjml><mj-body><mj-text>'.$data['name'].'</mj-text></mj-body></mjml>';
    }
}
