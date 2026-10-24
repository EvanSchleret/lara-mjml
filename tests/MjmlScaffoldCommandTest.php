<?php

use EvanSchleret\LaraMjml\Commands\MakeMjmlMailCommand;
use EvanSchleret\LaraMjml\Commands\MakeMjmlNotificationCommand;
use EvanSchleret\LaraMjml\Commands\MjmlInstallCommand;
use EvanSchleret\LaraMjml\Support\MjmlScaffoldGenerator;
use EvanSchleret\LaraMjml\Support\StubWriter;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

function scaffoldFilesystem(): Filesystem
{
    return new Filesystem;
}

function scaffoldRoot(Filesystem $files): string
{
    $path = sys_get_temp_dir().'/lara-mjml-scaffold-'.bin2hex(random_bytes(6));
    $files->makeDirectory($path, 0755, true);

    return $path;
}

function scaffoldApplication(string $path): ScaffoldTestApplication
{
    return new ScaffoldTestApplication($path);
}

function scaffoldGenerator(Filesystem $files): MjmlScaffoldGenerator
{
    return new MjmlScaffoldGenerator(new StubWriter($files));
}

function scaffoldTester(object $command, ScaffoldTestApplication $application, array $input): CommandTester
{
    $command->setLaravel($application);
    $tester = new CommandTester($command);
    $tester->execute($input);

    return $tester;
}

it('installs the configuration layout and example without overwriting files', function () {
    $files = scaffoldFilesystem();
    $root = scaffoldRoot($files);
    $application = scaffoldApplication($root);

    try {
        $command = new MjmlInstallCommand(scaffoldGenerator($files));
        $tester = scaffoldTester($command, $application, []);

        $exampleMail = $root.'/app/Mail/ExampleMail.php';
        $exampleView = $root.'/resources/views/emails/example.mjml.blade.php';

        expect($tester->getStatusCode())->toBe(0);
        expect($files->exists($root.'/config/laramjml.php'))->toBeTrue();
        expect($files->exists($root.'/resources/views/layouts/mjml.blade.php'))->toBeTrue();
        expect($files->exists($exampleMail))->toBeTrue();
        expect($files->exists($exampleView))->toBeTrue();
        expect($files->get($exampleMail))->toContain("view: 'emails.example'");
        expect($files->get($exampleView))->toContain("@extends('layouts.mjml')");
        expect($tester->getDisplay())->toContain('laramjml:doctor');

        $files->put($exampleMail, 'custom mail');
        $secondTester = scaffoldTester(
            new MjmlInstallCommand(scaffoldGenerator($files)),
            $application,
            [],
        );

        expect($secondTester->getStatusCode())->toBe(0);
        expect($files->get($exampleMail))->toBe('custom mail');
        expect($secondTester->getDisplay())->toContain('already exists');
    } finally {
        $files->deleteDirectory($root);
    }
});

it('installs only shared files with without examples', function () {
    $files = scaffoldFilesystem();
    $root = scaffoldRoot($files);
    $application = scaffoldApplication($root);

    try {
        $tester = scaffoldTester(
            new MjmlInstallCommand(scaffoldGenerator($files)),
            $application,
            ['--without-examples' => true],
        );

        expect($tester->getStatusCode())->toBe(0);
        expect($files->exists($root.'/config/laramjml.php'))->toBeTrue();
        expect($files->exists($root.'/resources/views/layouts/mjml.blade.php'))->toBeTrue();
        expect($files->exists($root.'/app/Mail/ExampleMail.php'))->toBeFalse();
    } finally {
        $files->deleteDirectory($root);
    }
});

it('generates a mailable with a custom view', function () {
    $files = scaffoldFilesystem();
    $root = scaffoldRoot($files);
    $application = scaffoldApplication($root);

    try {
        $tester = scaffoldTester(
            new MakeMjmlMailCommand(scaffoldGenerator($files)),
            $application,
            [
                'name' => 'Admin/WelcomeMail',
                '--view' => 'emails.custom-welcome',
            ],
        );

        $classPath = $root.'/app/Mail/Admin/WelcomeMail.php';
        $viewPath = $root.'/resources/views/emails/custom-welcome.mjml.blade.php';

        expect($tester->getStatusCode())->toBe(0);
        expect($files->get($classPath))->toContain('namespace App\\Mail\\Admin;');
        expect($files->get($classPath))->toContain("view: 'emails.custom-welcome'");
        expect($files->exists($viewPath))->toBeTrue();
        expect($files->exists($root.'/resources/views/layouts/mjml.blade.php'))->toBeTrue();
    } finally {
        $files->deleteDirectory($root);
    }
});

it('generates a notification and skips existing files without force', function () {
    $files = scaffoldFilesystem();
    $root = scaffoldRoot($files);
    $application = scaffoldApplication($root);

    try {
        $command = new MakeMjmlNotificationCommand(scaffoldGenerator($files));
        scaffoldTester($command, $application, ['name' => 'InvoicePaid']);

        $classPath = $root.'/app/Notifications/InvoicePaid.php';
        $files->put($classPath, 'custom notification');

        $tester = scaffoldTester(
            new MakeMjmlNotificationCommand(scaffoldGenerator($files)),
            $application,
            ['name' => 'InvoicePaid'],
        );

        expect($tester->getStatusCode())->toBe(0);
        expect($files->get($classPath))->toBe('custom notification');
        expect($files->get($root.'/resources/views/emails/invoice-paid.mjml.blade.php'))
            ->toContain('Invoice Paid');
    } finally {
        $files->deleteDirectory($root);
    }
});

class ScaffoldTestApplication extends Container
{
    public function __construct(
        private string $root,
    ) {}

    public function basePath(string $path = ''): string
    {
        return $path === '' ? $this->root : $this->root.'/'.$path;
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config'.($path === '' ? '' : '/'.$path));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources'.($path === '' ? '' : '/'.$path));
    }

    public function getNamespace(): string
    {
        return 'App\\';
    }

    public function runningUnitTests(): bool
    {
        return true;
    }
}
