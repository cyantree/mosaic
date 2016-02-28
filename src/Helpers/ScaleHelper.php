<?php
namespace Cyantree\Mosaic\Helpers;

use Cyantree\Grout\Event\Event;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Mosaic\Mosaic;
use Cyantree\Mosaic\Types\Helper;

class ScaleHelper extends Helper
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

            $scale = $configuration->getTwoSide('scale', 1);

            if ($scale->sideA != 1 && $scale->sideB != 1) {
                $image = ImageTools::resizeImage($sprite->image, round($sprite->width * $scale->sideA), round($sprite->height * $scale->sideB), false, ImageTools::MODE_EXACT);

                $sprite->replaceImage($image);

                continue;
            }

            $width = $configuration->getInt('width');
            $height = $configuration->getInt('height');

            if (!$width && !$height) {
                return;
            }

            $newWidth = $width;
            $newHeight = $height;

            if ($width && $height) {

            }
            elseif ($width) {
                $newHeight = $newWidth / ($sprite->width / $sprite->height);
            }
            else {
                $newWidth = $newHeight * ($sprite->width / $sprite->height);
            }

            if ($newWidth != $sprite->width || $newHeight != $sprite->height) {
                $image = ImageTools::resizeImage($sprite->image, $newWidth, $newHeight, false, ImageTools::MODE_EXACT);
                $sprite->replaceImage($image);
            }
        }
    }
}
