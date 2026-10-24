<?php

namespace EvanSchleret\LaraMjml\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

final class MjmlScaffoldGenerator
{
    public function __construct(
        private StubWriter $writer,
    ) {}

    public function writeConfig(string $configPath, bool $force = false): array
    {
        $path = dirname(__DIR__, 2).'/config/laramjml.php';

        return [
            'label' => 'Configuration',
            'path' => $configPath,
            'written' => $this->writer->writeFile($path, $configPath, $force),
        ];
    }

    public function writeLayout(string $viewsPath, bool $force = false): array
    {
        $path = $viewsPath.'/layouts/mjml.blade.php';

        return [
            'label' => 'Layout',
            'path' => $path,
            'written' => $this->writer->writeStub('layout.blade.php', $path, [], $force),
        ];
    }

    public function writeMail(
        string $basePath,
        string $applicationNamespace,
        string $name,
        ?string $view,
        bool $force = false,
    ): array {
        return $this->writeClassAndView(
            basePath: $basePath,
            applicationNamespace: $applicationNamespace,
            name: $name,
            view: $view,
            type: 'Mail',
            suffix: 'Mail',
            stub: 'mail.php',
            force: $force,
        );
    }

    public function writeNotification(
        string $basePath,
        string $applicationNamespace,
        string $name,
        ?string $view,
        bool $force = false,
    ): array {
        return $this->writeClassAndView(
            basePath: $basePath,
            applicationNamespace: $applicationNamespace,
            name: $name,
            view: $view,
            type: 'Notifications',
            suffix: 'Notification',
            stub: 'notification.php',
            force: $force,
        );
    }

    private function writeClassAndView(
        string $basePath,
        string $applicationNamespace,
        string $name,
        ?string $view,
        string $type,
        string $suffix,
        string $stub,
        bool $force,
    ): array {
        [$class, $segments] = $this->classParts($name);
        $namespace = trim($applicationNamespace, '\\').'\\'.$type;
        $relativeNamespace = '';

        if ($segments !== []) {
            $relativeNamespace = '\\'.implode('\\', $segments);
            $namespace .= $relativeNamespace;
        }

        $classPath = $basePath.'/app/'.implode('/', array_merge([$type], $segments, [$class.'.php']));
        $viewName = $this->viewName($view, $segments, $class, $suffix);
        $viewPath = $this->viewPath($basePath, $viewName);
        $subject = addcslashes(Str::headline($this->baseName($class, $suffix)), "\\'");
        $replacements = [
            'LaraMjmlGeneratedNamespace' => $namespace,
            'LaraMjmlGeneratedClass' => $class,
            'lara_mjml_generated_view' => $viewName,
            'LaraMjmlGeneratedSubject' => $subject,
        ];

        return [
            [
                'label' => $type === 'Mail' ? 'Mailable' : 'Notification',
                'path' => $classPath,
                'written' => $this->writer->writeStub($stub, $classPath, $replacements, $force),
            ],
            [
                'label' => 'View',
                'path' => $viewPath,
                'written' => $this->writer->writeStub('email.mjml.blade.php', $viewPath, $replacements, $force),
            ],
        ];
    }

    private function classParts(string $name): array
    {
        $normalized = trim(str_replace('\\', '/', $name), '/');
        $normalized = preg_replace('/\.php$/', '', $normalized) ?? $normalized;
        $parts = array_values(array_filter(explode('/', $normalized), static fn (string $part): bool => $part !== ''));

        if ($parts === []) {
            throw new InvalidArgumentException('A class name is required.');
        }

        $class = Str::studly(array_pop($parts));
        $segments = array_map(static fn (string $part): string => Str::studly($part), $parts);

        return [$class, $segments];
    }

    private function viewName(?string $view, array $segments, string $class, string $suffix): string
    {
        if (is_string($view) && trim($view) !== '') {
            $view = trim(str_replace('/', '.', $view));
            $view = preg_replace('/\.mjml\.blade\.php$|\.blade\.php$/', '', $view) ?? $view;

            return $view;
        }

        $viewSegments = array_map(static fn (string $segment): string => Str::kebab($segment), $segments);
        $viewSegments[] = Str::kebab($this->baseName($class, $suffix));

        return implode('.', array_merge(['emails'], $viewSegments));
    }

    private function viewPath(string $basePath, string $view): string
    {
        return $basePath.'/resources/views/'.str_replace('.', '/', $view).'.mjml.blade.php';
    }

    private function baseName(string $class, string $suffix): string
    {
        return Str::endsWith($class, $suffix) && strlen($class) > strlen($suffix)
            ? Str::beforeLast($class, $suffix)
            : $class;
    }
}
