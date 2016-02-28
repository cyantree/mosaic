<?php
namespace Cyantree\Mosaic\Types;

class Spritesheet
{
    public $width = 0;
    public $height = 0;

    public $path;

    public $name;

    /** @var Sprite[] */
    public $sprites = [];

    public function destroy()
    {
        foreach ($this->sprites as $sprite) {
            $sprite->destroy();
        }

        $this->sprites = null;
    }

    public function addSprite(Sprite $sprite)
    {
        $this->sprites[] = $sprite;

        $this->width = max($this->width, $sprite->spriteX + $sprite->spriteWidth);
        $this->height = max($this->height, $sprite->spriteY + $sprite->spriteHeight);
    }
}
