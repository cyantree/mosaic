<?php
namespace Cyantree\Mosaic\Types;

use Cyantree\Mosaic\Core\Configuration;
use Cyantree\Mosaic\Mosaic;

abstract class Renderer
{
    /** @var Configuration */
    protected $configuration;

    /** @var Mosaic */
    protected $mosaic;

    public function __construct(Mosaic $mosaic, Configuration $configuration)
    {
        $this->mosaic = $mosaic;
        $this->configuration = $configuration;
    }

    abstract public function render(Job $job);
}
