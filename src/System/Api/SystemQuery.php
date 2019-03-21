<?php
declare(strict_types=1);

namespace MyService\System\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use MyService\System\HealthCheckResolver;

final class SystemQuery implements EventEngineDescription
{
    /**
     * Default Query, used to perform health checks using the messagebox endpoint
     */
    const HEALTH_CHECK = 'HealthCheck';

    public static function describe(EventEngine $eventEngine): void
    {
        //Default query: can be used to check if service is up and running
        $eventEngine->registerQuery(self::HEALTH_CHECK) //<-- Payload schema is optional for queries
        ->resolveWith(HealthCheckResolver::class) //<-- Service id (usually FQCN) to get resolver from DI container
        ->setReturnType(SystemSchema::healthCheck()); //<-- Type returned by resolver
    }
}
