<?php
declare(strict_types = 1);

$config = include 'config.php';

$serviceFactory = new \MyService\ServiceFactory($config);

//@TODO use cached serviceFactoryMap for production
$container = new \EventEngine\Discolight\Discolight(
    $serviceFactory
);

$serviceFactory->setContainer($container);

return $container;
