<?php
namespace Cyantree\Mosaic\Renderers;

use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Mosaic\Tools;
use Cyantree\Mosaic\Types\Job;
use Cyantree\Mosaic\Types\Renderer;
use Cyantree\Mosaic\Types\Spritesheet;

class BasicRenderer extends Renderer
{
    public function render(Job $job)
    {
        $format = $this->configuration->getString('format', 'png');

        $defaultColor = $format == 'png' ? 0x00000000 : 0xffffffff;
        $backgroundColor = $this->configuration->getColor('background', $defaultColor);

        foreach ($job->spritesheets as $spritesheet) {
            /** @var Spritesheet $spritesheet */

            $image = ImageTools::createImage($spritesheet->width, $spritesheet->height, $backgroundColor);

            foreach ($spritesheet->sprites as $sprite) {
                $repeatY = $sprite->repeatY;
                while ($repeatY--) {
                    $repeatX = $sprite->repeatX;

                    while ($repeatX--) {
                        imagecopy(
                            $image,
                            $sprite->image,
                            $sprite->spriteX + $sprite->paddingLeft + $repeatX * $sprite->width,
                            $sprite->spriteY + $sprite->paddingTop + $repeatY * $sprite->height,
                            0, 0,
                            $sprite->width, $sprite->height
                        );
                    }
                }
            }

            $output = $this->mosaic->getPath($this->configuration->getString('outputFolder'));
            $encodedSpritesheetPath = Tools::encodeFilePath($output . '/' . $spritesheet->name . '.' . $format);

            FileTools::createDirectory(dirname($encodedSpritesheetPath));


            if ($format == 'png') {
                imagepng($image, $encodedSpritesheetPath, 9, E_ALL);
            }
            elseif ($format == 'jpg') {
                imagejpeg($image, $encodedSpritesheetPath, $this->configuration->getInt('quality', 90));
            }

            imagedestroy($image);

            $encodedSpritesheetPath = realpath($encodedSpritesheetPath);

            if (!$encodedSpritesheetPath) {
                throw new \Exception('Could not verify rendered spritesheet path.');
            }

            // Normalize output path
            $spritesheet->path = Tools::decodeFilePath($encodedSpritesheetPath);
        }
    }
}
