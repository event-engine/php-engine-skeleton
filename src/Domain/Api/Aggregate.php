<?php

declare(strict_types=1);

namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;

class Aggregate implements EventEngineDescription
{
    /**
     * Define aggregate names using constants
     *
     * @example
     *
     * const USER = 'User';
     */


    /**
     * @param EventEngine $eventEngine
     */
    public static function describe(EventEngine $eventEngine): void
    {
        /**
         * Describe how your aggregates handle commands
         *
         * @example
         *
         * $eventEngine->process(Command::REGISTER_USER) <-- Command name of the command that is expected by the Aggregate's handle method
         *      ->withNew(self::USER) //<-- aggregate type, defined as constant above, also tell event engine that a new Aggregate should be created
         *      ->identifiedBy(Payload::USER_ID) //<-- Payload property (of all user related commands) that identify the addressed User
         *      ->handle([User::class, 'register']) //<-- Aggregates are stateless and have static callable methods that can be linked to using PHP's callable array syntax
         *      ->recordThat(Event::USER_REGISTERED) //<-- Event name of the event yielded by the Aggregate's handle method
         *      ->apply([User::class, 'whenUserRegistered']) //<-- Aggregate method (again static) that is called when event is recorded
         *      ->orRecordThat(Event::DOUBLE_REGISTRATION_DETECTED) //Alternative event that can be yielded by the Aggregate's handle method
         *      ->apply([User::class, 'whenDoubleRegistrationDetected']); //Again the method that should be called in case above event is recorded
         *
         * $eventEngine->process(Command::CHANGE_USERNAME) //<-- User::changeUsername() expects a Command::CHANGE_USERNAME command
         *      ->withExisting(self::USER) //<-- Aggregate should already exist, Event Engine uses Payload::USER_ID to load User from event store
         *      ->handle([User::class, 'changeUsername'])
         *      ->recordThat(Event::USERNAME_CHANGED)
         *      ->apply([User::class, 'whenUsernameChanged']);
         */
    }
}
