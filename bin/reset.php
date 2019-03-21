<?php
declare(strict_types = 1);

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

/** @var \Psr\Container\ContainerInterface $container */
$container = require 'config/container.php';

/** @var \EventEngine\EventEngine $eventEngine */
$eventEngine = $container->get(\EventEngine\EventEngine::class);

$eventEngine->bootstrap(getenv('PROOPH_ENV')?: 'prod', true);

/** @var \Prooph\EventStore\Projection\ProjectionManager $projectionManager */
$projectionManager = $container->get(\Prooph\EventStore\Projection\ProjectionManager::class);

echo "Resetting " . \MyService\Persistence\WriteModelStreamProjection::NAME . "\n";

$projectionManager->resetProjection(\MyService\Persistence\WriteModelStreamProjection::NAME);
