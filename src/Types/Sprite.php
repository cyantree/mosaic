<?php
namespace Cyantree\Mosaic\Types;

use Cyantree\Mosaic\Core\Configuration;

class Sprite
{
    public $enabled = true;

    public $originalName;
    public $name;
    public $image;
    public $width;
    public $height;

    public $path;

    public $paddingTop = 0;
    public $paddingRight = 0;
    public $paddingBottom = 0;
    public $paddingLeft = 0;

    public $marginTop = 0;
    public $marginRight = 0;
    public $marginBottom = 0;
    public $marginLeft = 0;

    public $spriteX = 0;
    public $spriteY = 0;
    public $spriteWidth;
    public $spriteHeight;
    public $spriteOuterWidth;
    public $spriteOuterHeight;

    public $repeatX = 1;
    public $repeatY = 1;

    /** @var Configuration */
    public $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function destroy()
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    public function calculateDimensions()
    {
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);

        $this->spriteWidth = $this->width * $this->repeatX + $this->paddingLeft + $this->paddingRight;
        $this->spriteHeight = $this->height * $this->repeatY + $this->paddingTop + $this->paddingBottom;

        $this->spriteOuterWidth = $this->spriteWidth + $this->marginLeft + $this->marginRight;
        $this->spriteOuterHeight = $this->spriteHeight + $this->marginTop + $this->marginBottom;
    }

    public function replaceImage($image)
    {
        if ($this->image) {
            imagedestroy($this->image);
        }

        $this->image = $image;
        $this->calculateDimensions();
    }
}
