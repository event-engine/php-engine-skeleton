<?php

declare(strict_types=1);

namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;

class Query implements EventEngineDescription
{
    /**
     * Define query names using constants
     *
     * For a clean and simple API it is recommended to just use the name of the "thing"
     * you want to return as query name, see example for user queries:
     *
     * @example
     *
     * const USER = 'User';
     * const USERS = 'Users';
     * const FRIENDS = 'Friends';
     */

    public static function describe(EventEngine $eventEngine): void
    {
        /**
         * Register queries and if they have arguments (like filters, skip, limit, orderBy arguments)
         * you define the schema of that arguments as query payload
         *
         * You also tell event engine which resolver should be used to resolve the query.
         * The resolver is requested from the PSR-11 DI container used by event engine.
         *
         * Each query also has a return type, which can be a JsonSchema::TYPE_ARRAY or one of the scalar JsonSchema types.
         * If the query returns an object (for example user data), this object should be registered in EventEngine as a Type
         * @see \MyService\Domain\Api\Type for details
         * @see \MyService\Domain\Api\Schema for best practise of how to reuse return type schemas
         *
         * @example
         *
         * //Register User query with Payload::USER_ID as required argument, Schema::userId() is reused here, so that only valid
         * //user ids are passed to the resolver
         * $eventEngine->registerQuery(self::User, JsonSchema::object([Payload::USER_ID => Schema::userId()]))
         *      ->resolveWith(UserResolver::class)
         *      ->setReturnType(Schema::user()); //<-- Pass type reference as return type, @see \MyService\Domain\Api\Schema::user() (in the comment) for details
         *
         * //Register a second query to load many Users, this query takes an optional Payload::ACTIVE argument
         * $eventEngine->registerQuery(self::Users, JsonSchema::object([], [
         *      Payload::ACTIVE => JsonSchema::nullOr(JsonSchema::boolean()) 
         * ]))
         *  ->resolveWith(UsersResolver::class)
         *  ->setReturnType(JsonSchema::array(Schema::user())); //<-- Return type is an array of Schema::user() (type reference to Type::USER)
         */
    }
}
