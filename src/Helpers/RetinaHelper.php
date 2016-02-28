<?php
namespace Cyantree\Mosaic\Helpers;

use Cyantree\Grout\Event\Event;
use Cyantree\Mosaic\Core\Configuration;
use Cyantree\Mosaic\Mosaic;
use Cyantree\Mosaic\Types\Helper;

class RetinaHelper extends Helper
{
    public function init()
    {
        $this->job->events->join(Mosaic::E_SPRITES_LOADED, [$this, 'onSpritesLoaded'], null, true);
    }

    public function onSpritesLoaded(Event $event)
    {
        $scssHelperName = $this->configuration->getString('scssHelper', Configuration::VALUE_REQUIRED);
        $scaleHelperName = $this->configuration->getString('scaleHelper', Configuration::VALUE_REQUIRED);

        if (!($this->job->getHelper($scssHelperName) instanceof ScssHelper)) {
            throw new \Exception('scssHelper is invalid');
        }

        if (!($this->job->getHelper($scaleHelperName) instanceof ScaleHelper)) {
            throw new \Exception('scaleHelper is invalid');
        }

        $steps = $this->configuration->getInt('steps', Configuration::VALUE_REQUIRED);
        $factor = $this->configuration->getInt('factor', Configuration::VALUE_REQUIRED);

        foreach ($this->job->sprites as $sprite) {
            $sprite->configuration->extend((object) [
                'helpers' => (object) [
                    $scaleHelperName => (object) [
                        'scale' => $factor / $steps
                    ]
                ]
            ]);
        }

        $this->job->configuration->extend((object) [
            'helpers' => (object) [
                $scssHelperName => (object) [
                    'retinaFactor' => $factor
                ]
            ]
        ]);
    }
}
