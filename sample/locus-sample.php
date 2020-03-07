<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Locus\Locus;

$locus = new Locus();

$config = [
    'credentials' => [
        'key' => '', // put here your aws key
        'secret' => '', // put here your aws secret
    ],
    'version' => 'latest', // service version
    'region' => 'us-east-1', // aws region
];
$namespace = 'dimi';
$service = 'back';

$env = [
    'back' => [
        'url' => 'urlFromEnv'
    ],
];

// init with env
$locus = new Locus(
    [],
    $env,
    $config
);

// clear cache
$locus->clearCache($service);

// get from env
$url = $locus->getUrl(
    $namespace,
    $service,
);
print_r('get from env:');
echo PHP_EOL;
print_r($url);
echo PHP_EOL;

// init without env
$locus = new Locus(
    [],
    [],
    $config
);

// get from sd
$url = $locus->getUrl(
    $namespace,
    $service,
);
print_r('get from sd:');
echo PHP_EOL;
print_r($url);
echo PHP_EOL;

// get from cache
$url = $locus->getUrl(
    $namespace,
    $service,
);
print_r('get from cache:');
echo PHP_EOL;
print_r($url);
echo PHP_EOL;
