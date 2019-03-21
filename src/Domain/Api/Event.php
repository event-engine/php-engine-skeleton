<?php

declare(strict_types=1);

namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;

class Event implements EventEngineDescription
{
    /**
     * Define event names using constants
     *
     * Note: It is NOT recommended to use a context in command and query names, see note in App\Api\Command.
     * But using a context in your event names is a good idea, because events tell other services in a system what
     * happened in your service. So these foreign services need to know the origin of the event.
     * A very simple way is to put the context in the event name separated by a dot. When using a message broker like
     * RabbitMQ you can use such a naming convention to route events of a certain context to a dedicated queue.
     *
     * @example
     *
     * const EVENT_CONTEXT = 'MyContext.';
     * const USER_REGISTERED = self::EVENT_CONTEXT.'UserRegistered';
     */

    /**
     * @param EventEngine $eventEngine
     */
    public static function describe(EventEngine $eventEngine): void
    {
        /**
         * Describe events produced or consumed by the service and corresponding payload schema (used for input validation)
         *
         * @example
         *
         * $eventEngine->registerEvent(
         *      self::USER_REGISTERED,
         *      JsonSchema::object([
         *          Payload::USER_ID => Schema::userId(), //<-- We only work with constants and domain specific reusable schemas
         *          Payload::USERNAME => Schema::username(), //<-- See MyService\Domain\Api\Payload for property constants ...
         *          Payload::EMAIL => Schema::email(), //<-- ... and MyService\Domain\Api\Schema for schema definitions
         *                                             // See also MyService\Domain\Api\Command, same schema definitions are used there
         *      ])
         * );
         */
    }
}
