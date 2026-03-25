<?php

use EvanSchleret\LaraMjml\Views\Engines\MJMLEngine;
use Illuminate\Config\Repository;
use Illuminate\Contracts\View\Engine as ViewEngine;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlResult;

beforeEach(function () {
    $this->config = new Repository([
        'laramjml' => [
            'beautify' => false,
            'minify' => true,
            'keep_comments' => false,
            'options' => [],
            'binary_path' => null,
        ],
    ]);

    putenv('MJML_NODE_PATH'); // Ensure a clean slate for each test.
});

it('converts rendered mjml from the underlying blade engine', function () {
    $bladeEngine = new FakeBladeEngine(
        fn (string $path, array $data) => "<mjml><mj-body><mj-text>{$data['content']}</mj-text></mj-body></mjml>"
    );

    $fakeMjml = new FakeMjml('<html><body>converted</body></html>');

    $engine = new MJMLEngine($bladeEngine, $this->config, fn () => $fakeMjml);

    $result = $engine->get('emails/invite.mjml.blade.php', ['content' => 'Hello']);

    expect($result)->toBe('<html><body>converted</body></html>');
    expect($bladeEngine->calls)->toHaveCount(1);
    expect($bladeEngine->calls[0]['path'])->toBe('emails/invite.mjml.blade.php');
    expect($bladeEngine->calls[0]['data']['content'])->toBe('Hello');

    expect($fakeMjml->convertCalls)->toHaveCount(1);
    expect($fakeMjml->convertCalls[0]['mjml'])->toContain('Hello');
    expect($fakeMjml->convertCalls[0]['options'])->toBe([]);
});

it('respects mjml configuration options', function () {
    $this->config->set('laramjml.beautify', true);
    $this->config->set('laramjml.minify', false);
    $this->config->set('laramjml.keep_comments', true);
    $this->config->set('laramjml.options', ['validationLevel' => 'soft']);
    $this->config->set('laramjml.binary_path', '/custom/node/bin');

    $bladeEngine = new FakeBladeEngine(fn () => '<mjml></mjml>');
    $fakeMjml = new FakeMjml('<html />');

    $engine = new MJMLEngine($bladeEngine, $this->config, fn () => $fakeMjml);

    $engine->get('path', []);

    expect($fakeMjml->flags['beautify'])->toBe([true]);
    expect($fakeMjml->flags['minify'])->toBe([false]);
    expect($fakeMjml->flags['keepComments'])->toBe([true]);
    expect($fakeMjml->convertCalls[0]['options'])->toBe(['validationLevel' => 'soft']);
    expect(getenv('MJML_NODE_PATH'))->toBe('/custom/node/bin');
});

it('falls back to empty options when laramjml options is not an array', function () {
    $this->config->set('laramjml.options', 'invalid-options');

    $bladeEngine = new FakeBladeEngine(fn () => '<mjml></mjml>');
    $fakeMjml = new FakeMjml('<html />');

    $engine = new MJMLEngine($bladeEngine, $this->config, fn () => $fakeMjml);

    $engine->get('path', []);

    expect($fakeMjml->convertCalls[0]['options'])->toBe([]);
});

it('does not set MJML_NODE_PATH when binary path is an empty string', function () {
    putenv('MJML_NODE_PATH=/existing/path');
    $this->config->set('laramjml.binary_path', '');

    $bladeEngine = new FakeBladeEngine(fn () => '<mjml></mjml>');
    $fakeMjml = new FakeMjml('<html />');

    $engine = new MJMLEngine($bladeEngine, $this->config, fn () => $fakeMjml);

    $engine->get('path', []);

    expect(getenv('MJML_NODE_PATH'))->toBe('/existing/path');
});

it('does not set MJML_NODE_PATH when binary path is not a string', function () {
    putenv('MJML_NODE_PATH=/existing/path');
    $this->config->set('laramjml.binary_path', true);

    $bladeEngine = new FakeBladeEngine(fn () => '<mjml></mjml>');
    $fakeMjml = new FakeMjml('<html />');

    $engine = new MJMLEngine($bladeEngine, $this->config, fn () => $fakeMjml);

    $engine->get('path', []);

    expect(getenv('MJML_NODE_PATH'))->toBe('/existing/path');
});

class FakeBladeEngine implements ViewEngine
{
    /**
     * @var array<int, array{path: string, data: array}>
     */
    public array $calls = [];

    /**
     * @param  callable(string, array): string  $renderer
     */
    public function __construct(
        private $renderer,
    ) {}

    public function get($path, array $data = [])
    {
        $this->calls[] = compact('path', 'data');

        return ($this->renderer)($path, $data);
    }
}

class FakeMjml extends Mjml
{
    /**
     * @var array<string, array<int, bool>>
     */
    public array $flags = [
        'beautify' => [],
        'minify' => [],
        'keepComments' => [],
    ];

    /**
     * @var array<int, array{mjml: string, options: array}>
     */
    public array $convertCalls = [];

    public function __construct(
        private string $htmlToReturn,
    ) {
        parent::__construct();
    }

    public function beautify(bool $beautify = true): self
    {
        $this->flags['beautify'][] = $beautify;

        return $this;
    }

    public function minify(bool $minify = true): self
    {
        $this->flags['minify'][] = $minify;

        return $this;
    }

    public function keepComments(bool $keepComments = true): self
    {
        $this->flags['keepComments'][] = $keepComments;

        return $this;
    }

    public function convert(string $mjml, array $options = []): MjmlResult
    {
        $this->convertCalls[] = compact('mjml', 'options');

        return new MjmlResult([
            'html' => $this->htmlToReturn,
            'json' => [],
            'errors' => [],
        ]);
    }
}
