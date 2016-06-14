<?php
namespace Cyantree\Mosaic\Layouters;

use Cyantree\Mosaic\Core\Configuration;
use Cyantree\Mosaic\Types\Job;
use Cyantree\Mosaic\Types\Layouter;
use Cyantree\Mosaic\Layouters\SmartLayouter\GrowingPacker;
use Cyantree\Mosaic\Layouters\SmartLayouter\GrowingPackerBlock;
use Cyantree\Mosaic\Types\Sprite;

class SmartLayouter extends Layouter
{
    public function layout(Job $job)
    {
        $packer = new GrowingPacker();
        $packer->maxWidth = $this->configuration->getInt('maxWidth', Configuration::VALUE_REQUIRED);
        $packer->maxHeight = $this->configuration->getInt('maxHeight', Configuration::VALUE_REQUIRED);

        $unpackedBlocks = [];
        foreach ($job->sprites as $sprite) {
            if (!$sprite->enabled) {
                continue;
            }

            $block = new GrowingPackerBlock($sprite->spriteOuterWidth, $sprite->spriteOuterHeight);
            $block->data = $sprite;

            $unpackedBlocks[] = $block;
        }

        $unpackedBlocks = $packer->sortBlocks($unpackedBlocks);

        while (count($unpackedBlocks)) {
            $spritesheet = $job->createSpritesheet();

            $result = $packer->pack($unpackedBlocks);

            foreach ($result->packedBlocks as $block) {
                /** @var Sprite $sprite */
                $sprite = $block->data;
                $sprite->spriteX = $block->node->x;
                $sprite->spriteY = $block->node->y;

                $spritesheet->addSprite($sprite);
            }

            $unpackedBlocks = $result->unpackedBlocks;
        }
    }
}
