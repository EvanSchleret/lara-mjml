<?php

use EvanSchleret\LaraMjml\Commands\MjmlPreviewCommand;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

function previewConfig(): Repository
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

function previewApplication(string $root): PreviewTestApplication
{
    return new PreviewTestApplication($root);
}

function previewTester(MjmlPreviewCommand $command, PreviewTestApplication $application, array $input): CommandTester
{
    $command->setLaravel($application);
    $tester = new CommandTester($command);
    $tester->execute($input);

    return $tester;
}

it('renders data to a configured output without opening the browser', function () {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/lara-mjml-preview-'.bin2hex(random_bytes(6));
    $files->makeDirectory($root, 0755, true);
    $opened = false;

    try {
        $command = new MjmlPreviewCommand(
            previewConfig(),
            $files,
            fn (string $view, array $data): string => '<html>'.$view.' '.$data['name'].'</html>',
            function () use (&$opened): bool {
                $opened = true;

                return true;
            },
        );

        $tester = previewTester($command, previewApplication($root), [
            'view' => 'emails.example',
            '--data' => '{"name":"John"}',
            '--output' => 'storage/preview.html',
            '--no-open' => true,
        ]);

        $output = $root.'/storage/preview.html';

        expect($tester->getStatusCode())->toBe(0);
        expect($files->get($output))->toBe('<html>emails.example John</html>');
        expect($opened)->toBeFalse();
        expect($tester->getDisplay())->toContain('Preview generated: '.$output);
    } finally {
        $files->deleteDirectory($root);
    }
});

it('opens a generated preview when requested', function () {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/lara-mjml-preview-'.bin2hex(random_bytes(6));
    $files->makeDirectory($root, 0755, true);
    $openedPath = null;

    try {
        $command = new MjmlPreviewCommand(
            previewConfig(),
            $files,
            fn (string $view, array $data): string => '<html />',
            function (string $path) use (&$openedPath): bool {
                $openedPath = $path;

                return true;
            },
        );

        $output = $root.'/preview.html';
        $tester = previewTester($command, previewApplication($root), [
            'view' => 'emails.example',
            '--output' => $output,
        ]);

        expect($tester->getStatusCode())->toBe(0);
        expect($openedPath)->toBe($output);
        expect($tester->getDisplay())->toContain('opened in the browser');
    } finally {
        $files->deleteDirectory($root);
    }
});

it('rejects invalid preview data', function () {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/lara-mjml-preview-'.bin2hex(random_bytes(6));
    $files->makeDirectory($root, 0755, true);

    try {
        $command = new MjmlPreviewCommand(
            previewConfig(),
            $files,
            fn (string $view, array $data): string => '<html />',
            fn (string $path): bool => true,
        );

        $tester = previewTester($command, previewApplication($root), [
            'view' => 'emails.example',
            '--data' => '{invalid-json}',
        ]);

        expect($tester->getStatusCode())->toBe(1);
        expect($tester->getDisplay())->toContain('Invalid JSON passed to --data');
    } finally {
        $files->deleteDirectory($root);
    }
});

it('returns a failure when preview rendering fails', function () {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/lara-mjml-preview-'.bin2hex(random_bytes(6));
    $files->makeDirectory($root, 0755, true);

    try {
        $command = new MjmlPreviewCommand(
            previewConfig(),
            $files,
            fn (string $view, array $data): string => throw new RuntimeException('MJML is unavailable'),
            fn (string $path): bool => true,
        );

        $tester = previewTester($command, previewApplication($root), [
            'view' => 'emails.example',
        ]);

        expect($tester->getStatusCode())->toBe(1);
        expect($tester->getDisplay())->toContain('Preview failed: MJML is unavailable');
    } finally {
        $files->deleteDirectory($root);
    }
});

class PreviewTestApplication extends Container
{
    public function __construct(
        private string $root,
    ) {}

    public function basePath(string $path = ''): string
    {
        return $path === '' ? $this->root : $this->root.'/'.$path;
    }

    public function runningUnitTests(): bool
    {
        return true;
    }
}
