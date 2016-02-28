<?php
namespace Cyantree\Mosaic\Outputs;

class ReadableOutput extends Output
{
    public function output($jobs)
    {
        echo 'Generation finished.' . PHP_EOL;
        echo 'Generated spritesheets:' . PHP_EOL;

        foreach ($jobs as $job) {
            foreach ($job->spritesheets as $spritesheet) {
                echo $spritesheet->path . PHP_EOL;
            }
        }
    }
}
