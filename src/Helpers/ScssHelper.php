<?php
namespace Cyantree\Mosaic\Helpers;

use Cyantree\Grout\Tools\FileTools;
use Cyantree\Mosaic\Mosaic;
use Cyantree\Mosaic\Types\Helper;
use Cyantree\Grout\Event\Event;
use Cyantree\Mosaic\Tools;

class ScssHelper extends Helper
{
    public function init()
    {
        $this->job->events->join(Mosaic::E_RENDERED, [$this, 'onRendered']);
    }

    public function onRendered(Event $e)
    {
        $scssContent = '';
        $scssVariablesContent = '';

        foreach ($this->job->spritesheets as $spritesheet) {
            $spritesheetName = $spritesheet->name;
            $spritePrefix = $this->configuration->getString('classPrefix', $this->job->configuration->getString('name') . '-');
            $scssPrefix = $this->configuration->getString('scssPrefix', $spritePrefix);

            $image = $this->configuration->getString('spritesheetFolder') . basename($spritesheet->path);

            if ($this->configuration->getBool('refreshCache')) {
                $image .= '?_' . sha1_file($spritesheet->path);
            }

            $retinaFactor = $this->configuration->getInt('retinaFactor', 1);
            $retinaThreshold = $retinaFactor - .75;
            $retinaThresholdPx = round($retinaThreshold * 96);
            $retinaEnabled = $retinaFactor > 1;

            $indent = str_repeat(' ', 4);

            $scssContent .= <<<SCSS
@mixin spritesheet-{$spritesheetName}() {
    display: inline-block;
    overflow: hidden;
    background-repeat: no-repeat;
    background-image: url({$image});

SCSS;

            $mixinsContent = '';

            if ($retinaEnabled) {
                $scssContent .=  $indent . 'background-size: ' . self::cssFormatPx($spritesheet->width / $retinaFactor) . ' auto;' . chr(10);
            }
            else {
                $scssContent .=  $indent . 'background-size: auto;' . chr(10);
            }

            $scssContent .= '}' . chr(10) . chr(10);

            if ($retinaEnabled) {
                $scssContent .= <<<SCSS
@media (min-device-pixel-ratio: {$retinaThreshold}), (min-resolution: {$retinaThresholdPx}dpi) {

SCSS;
            }

            foreach ($spritesheet->sprites as $sprite) {
                $configuration = $this->getSpriteConfiguration($sprite);

                $spriteName = $spritePrefix . $sprite->name;
                $scssName = $scssPrefix . $sprite->name;

                $realSpriteWidth = $sprite->spriteWidth;
                $realSpriteHeight = $sprite->spriteHeight;
                $realSpriteX = $sprite->spriteX;
                $realSpriteY = $sprite->spriteY;

                if ($retinaEnabled) {
                    $realSpriteWidth = $realSpriteWidth / $retinaFactor;
                    $realSpriteHeight = $realSpriteHeight / $retinaFactor;
                    $realSpriteX = $realSpriteX / $retinaFactor;
                    $realSpriteY = $realSpriteY / $retinaFactor;
                }

                $spriteContent = $indent . $indent . '@include spritesheet-' . $spritesheetName . '();' . chr(10);

                if ($configuration->getBool('center')) {
                    $spriteContent .= $indent . $indent . 'margin-left: ' . self::cssFormatPx(-$realSpriteWidth / $retinaFactor) . ';' . chr(10);
                    $spriteContent .= $indent . $indent . 'margin-top: ' . self::cssFormatPx(-$realSpriteHeight / $retinaFactor) . ';' . chr(10);
                }

                $spriteContent .= $indent . $indent . 'width: ' . self::cssFormatPx($realSpriteWidth) . ';' . chr(10);
                $spriteContent .= $indent . $indent . 'height: ' . self::cssFormatPx($realSpriteHeight) . ';' . chr(10);
                $spriteContent .= $indent . $indent . 'background-position: ' . self::cssFormatPx(-$realSpriteX) . ' ' . self::cssFormatPx(-$realSpriteY) . ';' . chr(10);

                foreach ($configuration->getNode('styles') as $styleName => $styleValue) {
                    $spriteContent .= $indent . $indent . $styleName . ': ' . $styleValue . ';' . chr(10);
                }

                if ($configuration->getBool('createClass', true)) {
                    // Write class
                    $scssContent .= $indent . '.' . $spriteName . ' {'  . chr(10) .
                            $spriteContent .
                            $indent . '}' . chr(10) . chr(10);
                }

                if ($configuration->getBool('createMixin', true)) {
                    // Write mixin
                    $mixinsContent .= chr(10) . $indent . '@mixin ' . $scssName . ' {'  . chr(10) .
                            $spriteContent .
                            $indent . '}' . chr(10);
                }

                if ($configuration->getBool('createVariables', true)) {
                    $scssVariablesContent .= '$' . $scssName . '-width: ' . self::cssFormatPx($sprite->spriteWidth) . ';' . chr(10);
                    $scssVariablesContent .= '$' . $scssName . '-height: ' . self::cssFormatPx($sprite->spriteHeight) . ';' . chr(10);
                    $scssVariablesContent .= '$' . $scssName . '-x: ' . self::cssFormatPx(-$sprite->spriteX) . ';' . chr(10);
                    $scssVariablesContent .= '$' . $scssName . '-y: ' . self::cssFormatPx(-$sprite->spriteY) . ';' . chr(10);
                    $scssVariablesContent .= "\${$scssName}-image: '{$image}';" . chr(10);
                    $scssVariablesContent .= "\${$scssName}-retina-factor: $retinaFactor;" . chr(10);
                    $scssVariablesContent .= chr(10);
                }
            }

            if ($retinaEnabled) {
                $scssContent .= ' }' . chr(10);
            }

            if ($mixinsContent) {
                $scssContent .= chr(10) . $mixinsContent;
            }
        }

        $outputFolder = $this->mosaic->getPath($this->configuration->getString('outputFolder'));
        $outputFileBasename = $this->configuration->getString('outputFile', '_' . $this->job->configuration->getString('name'));

        $outputPath = $outputFolder . $outputFileBasename . '.scss';
        $outputPathVariables = $outputFolder . $outputFileBasename . '_variables.scss';

        $scssFiles = [
                $outputPath => $scssContent,
                $outputPathVariables => $scssVariablesContent
        ];

        foreach ($scssFiles as $path => $content) {
            $path = Tools::encodeFilePath($path);

            FileTools::createDirectory(dirname($path));

            file_put_contents($path, $content);
        }
    }

    private static function cssFormatPx($px, $round = true)
    {
        if ($px == 0) {
            return '0';
        }
        else {
            return ($round ? round($px) : $px) . 'px';
        }
    }
}
