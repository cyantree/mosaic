<?php
use Cyantree\Grout\AutoLoader;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Mosaic\Outputs\ReadableOutput;
use Cyantree\Mosaic\Outputs\JsonOutput;
use Cyantree\Mosaic\Mosaic;

require_once(__DIR__ . '/../../../autoload.php');

ini_set('memory_limit', '1024M');

AutoLoader::init();

$options = new ArrayFilter(getopt('f:c:j:o:', ['debug']));

$outputMode = $options->get('o', 'readable');

$s = new Mosaic();
$s->debug = $options->has('debug');
$s->basePath = realpath(dirname($options->needs('f'))) . '/';
$s->applicationPath = __DIR__ . '/../src/';

$s->configuration->load($options->needs('f'));

if ($options->has('c')) {
    $s->configuration->extend(json_decode(rawurldecode($options->get('c'))));
}

if ($options->has('j')) {
    $includedJobs = explode(',', $options->get('j'));
}
else {
    $includedJobs = null;
}

$jobs = $s->compile($includedJobs);

switch ($outputMode) {
    case 'json':
        $output = new JsonOutput();
        break;
    default:
        $output = new ReadableOutput();
        break;
}

$output->output($jobs);
