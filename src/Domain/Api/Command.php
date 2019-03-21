<?php

declare(strict_types=1);

namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;

class Command implements EventEngineDescription
{
    /**
     * Define command names using constants
     *
     * Note: Event engine is best suited for single context services.
     * So in most cases you don't need to set a context in front of your commands because the context
     * is defined by the service boundaries itself, but the example includes a context to be complete.
     *
     * @example
     *
     * const COMMAND_CONTEXT = 'MyContext.';
     * const REGISTER_USER = self::COMMAND_CONTEXT . 'RegisterUser';
     */


    /**
     * @param EventEngine $eventEngine
     */
    public static function describe(EventEngine $eventEngine): void
    {
        /**
         * Describe commands of the service and corresponding payload schema (used for input validation)
         *
         * @example
         *
         * $eventEngine->registerCommand(
         *      self::REGISTER_USER,  //<-- Name of the  command defined as constant above
         *      JsonSchema::object([
         *          Payload::USER_ID => Schema::userId(), //<-- We only work with constants and domain specific reusable schemas
         *          Payload::USERNAME => Schema::username(), //<-- See MyService\Domain\Api\Payload for property constants ...
         *          Payload::EMAIL => Schema::email(), //<-- ... and MyService\Domain\Api\Schema for schema definitions
         *      ])
         * );
         */
    }
}
