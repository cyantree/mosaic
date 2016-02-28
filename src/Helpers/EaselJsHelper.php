<?php
namespace Cyantree\Mosaic\Helpers;

use Cyantree\Grout\Tools\FileTools;
use Cyantree\Mosaic\Mosaic;
use Cyantree\Mosaic\Types\Helper;
use Cyantree\Grout\Event\Event;
use Cyantree\Mosaic\Tools;

class EaselJsHelper extends Helper
{
    public function init()
    {
        $this->job->events->join(Mosaic::E_RENDERED, [$this, 'onRendered']);
    }

    public function onRendered(Event $e)
    {
        $json = [
            'images' => [],
            'frames' => [],
            'animations' => []
        ];

        if ($framerate = $this->configuration->getFloat('framerate')) {
            $json['framerate'] = $framerate;
        }

        $spritesheetIndex = -1;
        $spriteIndex = -1;
        foreach ($this->job->spritesheets as $spritesheet) {
            $spritesheetIndex++;

            $animations = [];

//            $animationPrefix = $this->_configuration->getString('animationPrefix', $this->_job->configuration->getString('name'));

            $json['images'][] = $this->configuration->getString('spritesheetFolder') . basename($spritesheet->path);

            foreach ($spritesheet->sprites as $sprite) {
                $spriteIndex++;

                $configuration = $this->getSpriteConfiguration($sprite);

                if (preg_match('!(.+)\{animation(@([0-9.]+))?(@([^@\}]+))?}!', $sprite->originalName, $animationData)) {
                    if (!isset($animations[$animationData[1]])) {
                        $animations[$animationData[1]] = [
                            'speed' => isset($animationData[3]) ? floatval($animationData[3]) : null,
                            'sprites' => [$spriteIndex],
                            'next' => isset($animationData[5]) ? $animationData[5] : null
                        ];

                        if (!$animations[$animationData[1]]['speed']) {
                            $animations[$animationData[1]]['speed'] = $configuration->getFloat('speed', 0);
                        }
                    }
                    else {
                        $animations[$animationData[1]]['sprites'][] = $spriteIndex;
                    }
                }

                $frameConfiguration = [
                    $sprite->spriteX,
                    $sprite->spriteY,
                    $sprite->spriteWidth,
                    $sprite->spriteHeight,
                    $spritesheetIndex
                ];

                if ($configuration->getBool('center')) {
                    $frameConfiguration[] = round($sprite->spriteWidth / 2);
                    $frameConfiguration[] = round($sprite->spriteHeight / 2);
                }

                $json['frames'][] = $frameConfiguration;

                $json['animations'][$sprite->name] = [$spriteIndex];
            }

            foreach ($animations as $name => $animation) {
                $ani = [
                    'frames' => $animation['sprites']
                ];

                if ($animation['speed']) {
                    $ani['speed'] = $animation['speed'];
                }

                if ($next = $animation['next']) {
                    if ($next == 'loop') {
                        $next = $name;
                    }

                    $ani['next'] = $next;
                }
                $json['animations'][$name] = $ani;
            }
        }

        $outputFolder = $this->mosaic->getPath($this->configuration->getString('outputFolder'));
        $outputFileBasename = $this->configuration->getString('outputFile', $this->job->configuration->getString('name'));

        $outputPath = $outputFolder . $outputFileBasename . '.json';

        $jsonFiles = [
            $outputPath => $json,
        ];

        foreach ($jsonFiles as $path => $content) {
            $path = Tools::encodeFilePath($path);

            FileTools::createDirectory(dirname($path));

            file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
