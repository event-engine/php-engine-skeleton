<?php

declare(strict_types=1);

namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;

class Listener implements EventEngineDescription
{

    public static function describe(EventEngine $eventEngine): void
    {
        /**
         * Register domain event listeners
         *
         * This can be anything f.e. a mailer, a process manager or a message producer as long as
         * it is a callable that takes the domain event as a single argument and is loadable from DI container
         *
         * @example
         *
         * $eventEngine->on(Event::USER_REGISTERED, VerificationMailer::class);
         */
    }
}
