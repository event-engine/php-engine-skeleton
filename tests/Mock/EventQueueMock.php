<?php
declare(strict_types=1);

namespace MyServiceTest\Mock;

use EventEngine\Messaging\Event;

interface EventQueueMock
{
    /**
     * @return Event[]
     */
    public function queuedEvents(): array;

    public function process(): void;
}
