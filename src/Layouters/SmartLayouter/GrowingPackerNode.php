<?php
namespace Cyantree\Mosaic\Layouters\SmartLayouter;

class GrowingPackerNode
{
    public $x;
    public $y;
    public $width;
    public $height;

    public $used = false;

    /** @var GrowingPackerNode */
    public $down;

    /** @var GrowingPackerNode */
    public $right;

    public function __construct($x = null, $y = null, $width = null, $height = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }
}
