<?php
namespace Cyantree\Mosaic\Types;

use Cyantree\Grout\Event\Events;
use Cyantree\Mosaic\Core\Configuration;

class Job
{
    /** @var Sprite[] */
    public $sprites = [];

    /** @var Spritesheet[] */
    public $spritesheets = [];

    /** @var Helper[] */
    public $helpers = [];

    /** @var Events */
    public $events;

    /** @var Configuration */
    public $configuration;

    private $countSpritesheets = 0;

    public function __construct()
    {
        $this->events = new Events();
        $this->configuration = new Configuration();
    }

    /** @return Spritesheet */
    public function createSpritesheet()
    {
        $s = new Spritesheet();
        $s->name = $this->configuration->getString('name');

        $this->countSpritesheets++;

        if ($this->countSpritesheets > 1) {
            $s->name .= '_' . $this->countSpritesheets;
        }

        $this->spritesheets[] = $s;

        return $s;
    }

    /** @return Helper|null */
    public function getHelper($id)
    {
        return isset($this->helpers[$id]) ? $this->helpers[$id] : null;
    }
}
