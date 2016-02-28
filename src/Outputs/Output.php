<?php
namespace Cyantree\Mosaic\Outputs;

use Cyantree\Mosaic\Types\Job;

abstract class Output
{
    /** @param $jobs Job[] */
    abstract public function output($jobs);
}
