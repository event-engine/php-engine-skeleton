<?php
declare(strict_types=1);

namespace MyService\System\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\Type\ObjectType;

final class SystemType implements EventEngineDescription
{
    const HEALTH_CHECK = 'HealthCheck';

    private static function healthCheck(): ObjectType
    {
        return JsonSchema::object([
            'system' => JsonSchema::boolean()
        ]);
    }

    public static function describe(EventEngine $eventEngine): void
    {
        $eventEngine->registerType(self::HEALTH_CHECK, self::healthCheck());
    }
}
