<?php
namespace Cyantree\Mosaic\Layouters\SmartLayouter;

class GrowingPackerBlock
{
    public $width;
    public $height;

    /** @var GrowingPackerNode */
    public $node;

    public $data;

    public function __construct($width = null, $height = null)
    {
        $this->width = $width;
        $this->height = $height;
    }
}
