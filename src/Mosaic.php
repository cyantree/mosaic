<?php
namespace Cyantree\Mosaic;

use Cyantree\Grout\AutoLoader;
use Cyantree\Mosaic\Core\Configuration;
use Cyantree\Mosaic\Core\SpriteLoader;
use Cyantree\Mosaic\Types\Helper;
use Cyantree\Mosaic\Types\Job;
use Cyantree\Mosaic\Types\Layouter;
use Cyantree\Mosaic\Types\Renderer;

class Mosaic
{
    const E_HELPERS_LOADED = 'helpersLoaded';
    const E_SPRITES_LOADED = 'spritesLoaded';
    const E_LAYOUTED = 'layouted';
    const E_RENDERED = 'rendered';

    /** @var Configuration */
    public $configuration;

    public $basePath;
    public $applicationPath;

    public $debug = false;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    /** @return Job[] */
    public function compile($includedJobs = null)
    {
        $jobs = [];

        if ($includedJobs !== null) {
            $includedJobs = array_flip($includedJobs);
        }

        foreach ($this->configuration->getNode('jobs') as $jobId => $jobConfiguration) {
            if ($includedJobs !== null && !array_key_exists($jobId, $includedJobs)) {
                continue;
            }

            $job = new Job();

            $job->configuration->extend($this->processConfigurationTemplates($this->configuration->getNode('default', null, true)));
            $job->configuration->extend($this->processConfigurationTemplates($this->configuration->getNode('sprites', null, true)), 'sprites');
            $job->configuration->extend($this->processConfigurationTemplates(Tools::deepCloneObject($jobConfiguration)));

            if (!$job->configuration->getBool('enabled', true)) {
                continue;
            }

            $this->loadHelpers($job);

            $this->loadSprites($job);

            $this->generateSpritesheets($job);

            $this->renderSpritesheets($job);

            $jobs[] = $job;
        }

        return $jobs;
    }

    private function loadHelpers(Job $job)
    {
        $helperConfiguration = $job->configuration->getSubConfiguration('helpers');

        $helperFolders = $helperConfiguration->get('folders');

        if ($helperFolders) {
            $helperFolders = Tools::singleOrMultipleValuesToArray($helperFolders);

            foreach ($helperFolders as $helperFolder) {
                AutoLoader::registerNamespace('', $this->basePath . $helperFolder);
            }
        }

        foreach ($helperConfiguration->configuration as $helperId => $helper) {
            if ($helperId == 'folders') {
                continue;
            }

            $helperConfig = new Configuration($helper);
            $helperClass = $helperConfig->getString('type');

            if (!$helperClass || !$helperConfig->getBool('enabled', true)) {
                continue;
            }

            /** @var Helper $helper */
            $helper = new $helperClass($this, $helperConfig, $job, $helperId);

            $helper->init();

            $job->helpers[$helperId] = $helper;
        }

        $job->events->trigger(self::E_HELPERS_LOADED);
    }

    private function loadSprites(Job $job)
    {
        $s = new SpriteLoader();
        $s->mosaic = $this;

        $folders = Tools::singleOrMultipleValuesToObject($job->configuration->get('folders'));

        foreach ($folders as $key => $folder) {
            $folders->{$key} = $this->basePath . $folder;
        }

        $s->folders = $folders;
        $s->load($job);

        $job->events->trigger(self::E_SPRITES_LOADED);
    }

    private function generateSpritesheets(Job $job)
    {
        $layouterConfig = $job->configuration->getSubConfiguration('layouter');

        $layouterClass = $layouterConfig->getString('type', 'Cyantree\Mosaic\Layouters\SmartLayouter');

        /** @var Layouter $layouter */
        $layouter = new $layouterClass($this, $layouterConfig);

        $layouter->layout($job);

        $job->events->trigger(self::E_LAYOUTED);
    }

    private function renderSpritesheets(Job $job)
    {
        $rendererConfig = $job->configuration->getSubConfiguration('renderer');
        $rendererClass = $rendererConfig->getString('type', 'Cyantree\Mosaic\Renderers\BasicRenderer');

        /** @var Renderer $renderer */
        $renderer = new $rendererClass($this, $rendererConfig);

        $renderer->render($job);

        $job->events->trigger(self::E_RENDERED);
    }

    public function getPath($path)
    {
        if (!preg_match('!^([a-zA-Z]:)?[/\\\]!', $path)) {
            return $this->basePath . $path;
        }

        return $path;
    }

    public function getApplicationPath($path = '')
    {
        return $this->applicationPath . $path;
    }

    private function processConfigurationTemplates($node)
    {
        if (!isset($node->templates)) {
            return $node;
        }

        $nodeCopy = Tools::deepCloneObject($node);

        $templates = Tools::singleOrMultipleValuesToArray($node->templates);

        foreach ($templates as $template) {
            $templateData = null;

            if (preg_match('!\.yml$!', $template)) {
                $templateData = Configuration::createFromFile($this->getPath($template))->configuration;
            }
            else {
                $subConfig = $this->configuration->getSubConfiguration('templates');

                if ($subConfig->has($template)) {
                    $templateData = $subConfig->getNode($template);
                }
                else {
                    $templateFile = $this->getApplicationPath('templates/' . $template . '.yml');

                    $templateData = Configuration::createFromFile($templateFile)->configuration;
                }
            }

            if ($templateData) {
                $this->processConfigurationTemplates($templateData);
                Tools::extendObject($node, $templateData);
            }
        }

        Tools::extendObject($node, $nodeCopy);

        return $node;
    }
}
