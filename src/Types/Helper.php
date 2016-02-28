<?php
namespace Cyantree\Mosaic\Types;

use Cyantree\Mosaic\Core\Configuration;
use Cyantree\Mosaic\Mosaic;

abstract class Helper
{
    /** @var Mosaic */
    protected $mosaic;

    /** @var Configuration */
    protected $configuration;

    /** @var Job */
    protected $job;

    protected $id;

    public function __construct(Mosaic $mosaic, Configuration $configuration, Job $job, $id)
    {
        $this->mosaic = $mosaic;
        $this->configuration = $configuration;
        $this->job = $job;
        $this->id = $id;
    }

    protected function getSpriteConfiguration(Sprite $sprite)
    {
        return $sprite->configuration->getSubConfiguration('helpers.' . $this->id);
    }

    abstract public function init();
}
