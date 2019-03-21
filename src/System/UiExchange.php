<?php

declare(strict_types=1);

namespace MyService\System;

use EventEngine\Messaging\Message;

interface UiExchange
{
    public function __invoke(Message $event): void;
}
