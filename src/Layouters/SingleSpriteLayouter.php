<?php
namespace Cyantree\Mosaic\Layouters;

use Cyantree\Mosaic\Types\Job;
use Cyantree\Mosaic\Types\Layouter;

class SingleSpriteLayouter extends Layouter
{
    public function layout(Job $job)
    {
        $naming = $this->configuration->getSubConfiguration('naming');

        $useSpriteName = $naming->getBool('useSpriteName');
        $prefix = $naming->getString('prefix');
        $suffix = $naming->getString('suffix');

        foreach ($job->sprites as $sprite) {
            if (!$sprite->enabled) {
                continue;
            }

            $sprite->spriteX = $sprite->spriteY = 0;

            $spritesheet = $job->createSpritesheet();

            $spritesheet->addSprite($sprite);

            if ($useSpriteName) {
                $spritesheet->name = $sprite->name;
            }

            $spritesheet->name = $prefix . $spritesheet->name . $suffix;
        }
    }
}
