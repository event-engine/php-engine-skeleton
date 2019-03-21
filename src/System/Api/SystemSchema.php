<?php
declare(strict_types=1);

namespace MyService\System\Api;

use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\Type\TypeRef;

final class SystemSchema
{
    public static function healthCheck(): TypeRef
    {
        //Health check schema is a type reference to the registered Type::HEALTH_CHECK
        return JsonSchema::typeRef(SystemType::HEALTH_CHECK);
    }
}
