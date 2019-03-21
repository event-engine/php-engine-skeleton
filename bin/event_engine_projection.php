<?php
declare(strict_types = 1);

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

/** @var \Psr\Container\ContainerInterface $container */
$container = require 'config/container.php';

/** @var \EventEngine\EventEngine $eventEngine */
$eventEngine = $container->get(\EventEngine\EventEngine::class);

/** @var \MyService\Persistence\WriteModelStreamProjection $writeModelStreamProjection */
$writeModelStreamProjection = $container->get(\MyService\Persistence\WriteModelStreamProjection::class);

$env = getenv('PROOPH_ENV')?: 'prod';

$eventEngine->bootstrap($env, true);

$devMode = $env === \EventEngine\EventEngine::ENV_DEV;

if($devMode) {
    $iterations = 0;

    while (true) {
        $writeModelStreamProjection->run(false);
        $iterations++;

        if($iterations > 100) {
            //force reload in dev mode by exiting with error so docker restarts the container
            exit(1);
        }

        usleep(100);
    }
} else {
    $writeModelStreamProjection->run();
}


