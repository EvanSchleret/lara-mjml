<?php

namespace EvanSchleret\LaraMjml\Views\Engines;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Engine;
use Spatie\Mjml\Mjml;

class MJMLEngine implements Engine
{
    /**
     * @var Closure(): Mjml
     */
    private Closure $mjmlFactory;

    public function __construct(
        private Engine $bladeEngine,
        private ConfigRepository $config,
        ?callable $mjmlFactory = null,
    ) {
        $this->mjmlFactory = $mjmlFactory
            ? Closure::fromCallable($mjmlFactory)
            : static fn (): Mjml => Mjml::new();
    }

    public function get($path, array $data = [])
    {
        $compiledView = $this->bladeEngine->get($path, $data);

        $options = $this->config->get('laramjml.options', []);

        if (! is_array($options)) {
            $options = [];
        }

        return $this->configureMjml(($this->mjmlFactory)())
            ->beautify((bool) $this->config->get('laramjml.beautify', false))
            ->minify((bool) $this->config->get('laramjml.minify', true))
            ->keepComments((bool) $this->config->get('laramjml.keep_comments', false))
            ->convert($compiledView, $options)
            ->html();
    }

    private function configureMjml(Mjml $mjml): Mjml
    {
        $binaryPath = $this->config->get('laramjml.binary_path');

        if (is_string($binaryPath) && $binaryPath !== '') {
            putenv('MJML_NODE_PATH='.$binaryPath);
        }

        return $mjml;
    }
}
