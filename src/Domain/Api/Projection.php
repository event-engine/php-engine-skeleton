<?php

declare(strict_types=1);

namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;

class Projection implements EventEngineDescription
{
    /**
     * You can register aggregate and custom projections in event engine
     *
     * For custom projection you should define a unique projection name using a constant
     *
     * const USER_FRIENDS = 'UserFriends';
     */

    /**
     * @param EventEngine $eventEngine
     */
    public static function describe(EventEngine $eventEngine): void
    {
        /**
         * Register an aggregate projection using the aggregate type as projection name
         *
         * $eventEngine->watch(\EventEngine\Persistence\Stream::ofWriteModel())
         *  ->withAggregateProjection(Aggregate::USER);
         *
         * Note: \EventEngine\Projecting\AggregateProjector::aggregateCollectionName(string $aggregateType)
         * will be used to generate a collection name for the aggregate data to be stored (as documents).
         * This means that a query resolver (@see \MyService\Domain\Api\Query) should use the same method to generate the collection name
         *
         * Register a custom projection
         *
         * $eventEngine->watch(\EventEngine\Persistence\Stream::ofWriteModel())
         *  ->with(self::USER_FRIENDS, UserFriendsProjector::class) //<-- Custom projection name and Projector service id (for DI container)
         *                                                          //Projector should implement EventEngine\Projecting\Projector
         *  ->filterEvents([Event::USER_ADDED, EVENT::FRIEND_LINKED]); //Projector is only interested in listed events
         */
    }
}
