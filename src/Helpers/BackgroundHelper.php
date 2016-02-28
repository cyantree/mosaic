<?php
namespace Cyantree\Mosaic\Helpers;

use Cyantree\Grout\Event\Event;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Mosaic\Mosaic;
use Cyantree\Mosaic\Types\Helper;

class BackgroundHelper extends Helper
{
    public function init()
    {
        $this->job->events->join(Mosaic::E_SPRITES_LOADED, [$this, 'onSpritesLoaded']);
    }

    public function onSpritesLoaded(Event $event)
    {
        foreach ($this->job->sprites as $sprite) {
            if (!$sprite->enabled) {
                continue;
            }

            $configuration = $this->getSpriteConfiguration($sprite);

            if (!$configuration->getBool('enabled', true)) {
                continue;
            }

            $backgroundColor = $configuration->getColor('color');
            $padding = $configuration->getFourSide('padding', 0);

            if ($backgroundColor) {
                $newImage = ImageTools::createImage($sprite->width + $padding->left + $padding->right, $sprite->height + $padding->top + $padding->bottom, $backgroundColor);

                imagealphablending($newImage, true);
                imagecopy($newImage, $sprite->image, $padding->left, $padding->top, 0, 0, $sprite->width, $sprite->height);

                $sprite->replaceImage($newImage);
            }
        }
    }
}
