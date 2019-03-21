<?php

declare(strict_types=1);

namespace MyService\System;

use EventEngine\Messaging\Message;
use EventEngine\Querying\Resolver;

final class HealthCheckResolver implements Resolver
{
    /**
     * @param Message $query
     * @return mixed
     */
    public function resolve(Message $query)
    {
        return [
            'system' => true
        ];
    }
}
