<?php
namespace Cyantree\Mosaic\Layouters\SmartLayouter;

class GrowingPackerResult
{
    public $width;

    public $height;

    /** @var GrowingPackerBlock[] */
    public $packedBlocks;

    /** @var GrowingPackerBlock[] */
    public $unpackedBlocks;

    /** @var GrowingPackerNode */
    public $root;

    public function __construct()
    {
        $this->packedBlocks = [];
        $this->unpackedBlocks = [];
    }

    public function initialize(GrowingPackerBlock $rootBlock)
    {
        $this->root = new GrowingPackerNode(0, 0, $rootBlock->width, $rootBlock->height);
        $this->width = $rootBlock->width;
        $this->height = $rootBlock->height;
    }

    public function addPackedBlock(GrowingPackerBlock $block)
    {
        $this->packedBlocks[] = $block;

        $this->width = max($this->width, $block->node->x + $block->width);
        $this->height = max($this->height, $block->node->y + $block->height);
    }

    public function addUnpackedBlock(GrowingPackerBlock $block)
    {
        $this->unpackedBlocks[] = $block;
    }
}
