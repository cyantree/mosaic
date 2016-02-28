<?php
namespace Cyantree\Mosaic\Outputs;

class JsonOutput extends Output
{
    public function output($jobs)
    {
        $paths = [];
        foreach ($jobs as $job) {
            foreach ($job->spritesheets as $spritesheet) {
                $paths[] = $spritesheet->path;
            }
        }

        echo json_encode($paths);
    }
}
