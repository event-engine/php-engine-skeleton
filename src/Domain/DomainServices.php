<?php
declare(strict_types=1);

namespace MyService\Domain;

use MyService\Domain\Api\Aggregate;
use MyService\Domain\Api\Command;
use MyService\Domain\Api\Event;
use MyService\Domain\Api\Listener;
use MyService\Domain\Api\Projection;
use MyService\Domain\Api\Query;
use MyService\Domain\Api\Type;

trait DomainServices
{
    public function domainDescriptions(): array
    {
        return [
            Type::class,
            Command::class,
            Event::class,
            Query::class,
            Aggregate::class,
            Projection::class,
            Listener::class,
        ];
    }
}
