<?php
namespace Cyantree\Mosaic\Core;

use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Mosaic\Mosaic;
use Cyantree\Mosaic\Tools;
use Cyantree\Mosaic\Types\Job;
use Cyantree\Mosaic\Types\Sprite;

class SpriteLoader
{
    /** @var Mosaic */
    public $mosaic;

    public $folders;

    public function load(Job $job)
    {
        $excludeFilters = $job->configuration->get('excludes', null);
        $includeFilters = $job->configuration->get('includes', null);

        /** @var Sprite[] $loadSprites */
        $loadSprites = [];

        foreach ($this->folders as $folder) {
            $files = FileTools::listDirectory($folder, false, true);

            foreach ($files as $file) {
                $originalSpriteName = pathinfo(str_replace('/', '-', $file), PATHINFO_FILENAME);
                $filteredSpriteName = preg_replace('/\{[^\}]+\}/', '', $originalSpriteName); // Strip {tags}

                $included = true;
                if ($excludeFilters !== null) {
                    $included = !Tools::matches($excludeFilters, $originalSpriteName) && !Tools::matches($excludeFilters, $filteredSpriteName);
                }
                elseif ($includeFilters !== null) {
                    $included = false;
                }

                if (!$included && $includeFilters !== null) {
                    $included = Tools::matches($includeFilters, $originalSpriteName) || Tools::matches($includeFilters, $filteredSpriteName);
                }

                if (!$included) {
                    continue;
                }

                $sprite = new Sprite();

                $sprite->originalName = $sprite->name = $originalSpriteName;
                $sprite->name = $filteredSpriteName;
                $sprite->path = $folder . $file;

                $loadSprites[$sprite->name] = $sprite;
            }
        }

        foreach ($loadSprites as $sprite) {
            $sprite->configuration->extend($job->configuration->configuration);

            $this->extendSpriteSettings($job, $sprite);

            if (!$sprite->configuration->getBool('enabled', true)) {
                continue;
            }

            $image = ImageTools::checkFile($sprite->path);

            if ($image->success) {
                $sprite->image = $image->image;

                $sprite->calculateDimensions();

                $job->sprites[$sprite->name] = $sprite;
            }
        }
    }

    private function extendSpriteSettings(Job $job, Sprite $sprite)
    {
        $spriteSettings = $job->configuration->getNode('sprites');

        foreach ($spriteSettings as $spriteSetting) {
            if ($spriteSetting === null) {
                // Empty node
                continue;
            }

            if (isset($spriteSetting->filters)) {
                $matches = Tools::matches($spriteSetting->filters, $sprite->name) || Tools::matches($spriteSetting->filters, $sprite->originalName);
            }
            else {
                $matches = true;
            }

            if (!$matches) {
                continue;
            }

            $attributes = new \stdClass();

            $passAttributes = ['name'];
            $passAttributes = array_flip($passAttributes);

            $excludeAttributes = ['filter'];
            $excludeAttributes = array_flip($excludeAttributes);

            foreach ($spriteSetting as $atbName => $atbValue) {
                if (array_key_exists($atbName, $excludeAttributes)) {
                    continue;
                }
                elseif (array_key_exists($atbName, $passAttributes)) {
                    $sprite->{$atbName} = $atbValue;
                }
                elseif ($atbName == 'margin') {
                    $s = Tools::decodeFourSide($atbValue);

                    if ($s) {
                        $sprite->marginTop = $s->top;
                        $sprite->marginRight = $s->right;
                        $sprite->marginBottom = $s->bottom;
                        $sprite->marginLeft = $s->left;
                    }
                }
                elseif ($atbName == 'padding') {
                    $s = Tools::decodeFourSide($atbValue);
                    
                    if ($s) {
                        $sprite->paddingTop = $s->top;
                        $sprite->paddingRight = $s->right;
                        $sprite->paddingBottom = $s->bottom;
                        $sprite->paddingLeft = $s->left;
                    }
                }
                else {
                    $attributes->{$atbName} = $atbValue;
                }
            }

            $sprite->configuration->extend($attributes);
        }
    }
}
