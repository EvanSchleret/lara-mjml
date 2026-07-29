<?php

use EvanSchleret\LaraMjml\Commands\MjmlDoctorCommand;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlResult;
use Symfony\Component\Console\Tester\CommandTester;

function doctorConfig(): Repository
{
    return new Repository([
        'laramjml' => [
            'binary_path' => null,
            'beautify' => false,
            'minify' => true,
            'keep_comments' => false,
            'options' => [],
        ],
    ]);
}

function doctorTester(MjmlDoctorCommand $command): CommandTester
{
    $command->setLaravel(new DoctorTestApplication);

    return new CommandTester($command);
}

class DoctorTestApplication extends Container
{
    public function runningUnitTests(): bool
    {
        return true;
    }
}

it('reports a healthy LaraMJML runtime', function () {
    $command = new MjmlDoctorCommand(
        doctorConfig(),
        fn (): string => 'node',
        fn (array $command): array => str_contains(implode(' ', $command), '--version')
            ? ['successful' => true, 'output' => 'v22.0.0', 'error' => '']
            : ['successful' => true, 'output' => '5.0.1', 'error' => ''],
        fn (): Mjml => new FakeDoctorMjml,
    );

    $tester = doctorTester($command);

    expect($tester->execute([]))->toBe(0);
    expect($tester->getDisplay())->toContain('OK   Node.js v22.0.0');
    expect($tester->getDisplay())->toContain('OK   MJML 5.0.1');
    expect($tester->getDisplay())->toContain('OK   MJML conversion');
    expect($tester->getDisplay())->toContain('LaraMJML is ready.');
});

it('fails when Node.js is unavailable', function () {
    $command = new MjmlDoctorCommand(
        doctorConfig(),
        fn (): ?string => null,
        fn (): array => throw new RuntimeException('The process runner should not be called.'),
        fn (): Mjml => new FakeDoctorMjml,
    );

    $tester = doctorTester($command);

    expect($tester->execute([]))->toBe(1);
    expect($tester->getDisplay())->toContain('Node.js is not available');
});

it('fails when MJML conversion is unavailable', function () {
    $command = new MjmlDoctorCommand(
        doctorConfig(),
        fn (): string => 'node',
        fn (array $command): array => ['successful' => true, 'output' => '5.0.1', 'error' => ''],
        fn (): Mjml => new FailingDoctorMjml,
    );

    $tester = doctorTester($command);

    expect($tester->execute([]))->toBe(1);
    expect($tester->getDisplay())->toContain('MJML conversion failed');
    expect($tester->getDisplay())->toContain('MJML conversion unavailable');
});

class FakeDoctorMjml extends Mjml
{
    public function __construct()
    {
        parent::__construct();
    }

    public function convert(string $mjml, array $options = []): MjmlResult
    {
        return new MjmlResult([
            'html' => '<html></html>',
            'json' => [],
            'errors' => [],
        ]);
    }
}

class FailingDoctorMjml extends Mjml
{
    public function __construct()
    {
        parent::__construct();
    }

    public function convert(string $mjml, array $options = []): MjmlResult
    {
        throw new RuntimeException('MJML conversion unavailable');
    }
}
