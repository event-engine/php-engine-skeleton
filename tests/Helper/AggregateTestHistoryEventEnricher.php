<?php

declare(strict_types=1);

namespace MyServiceTest\Helper;

use EventEngine\Messaging\GenericEvent;
use EventEngine\Messaging\Message;
use EventEngine\Runtime\Flavour;

final class AggregateTestHistoryEventEnricher
{
    public static function enrichHistory(array $history, array $aggregateDefinitions, Flavour $flavour): array
    {
        $enrichedHistory = [];

        $aggregateMap = [];

        /** @var Message $event */
        foreach ($history as $event) {
            $aggregateDefinition = self::getAggregateDescriptionByEvent($event->messageName(), $aggregateDefinitions);

            if (! $aggregateDefinition) {
                throw new \InvalidArgumentException('Unable to find aggregate description for event with name: ' . $event->messageName());
            }

            $serializedEvent = $flavour->prepareNetworkTransmission($event);

            $arId = $serializedEvent->getOrDefault($aggregateDefinition['aggregateIdentifier'], null);

            if (! $arId) {
                throw new \InvalidArgumentException(\sprintf(
                    'Event with name %s does not contain an aggregate identifier. Expected key was %s',
                    $event->messageName(),
                    $aggregateDefinition['aggregateIdentifier']
                ));
            }

            $serializedEvent = $serializedEvent->withAddedMetadata(GenericEvent::META_AGGREGATE_TYPE, $aggregateDefinition['aggregateType']);
            $serializedEvent = $serializedEvent->withAddedMetadata(GenericEvent::META_AGGREGATE_ID, $arId);

            $aggregateMap[$aggregateDefinition['aggregateType']][$arId][] = $serializedEvent;

            $serializedEvent = $serializedEvent->withAddedMetadata(GenericEvent::META_AGGREGATE_VERSION, \count($aggregateMap[$aggregateDefinition['aggregateType']][$arId]));

            if(! $serializedEvent instanceof GenericEvent) {
                $serializedEvent = GenericEvent::fromMessage($serializedEvent);
            }

            $enrichedHistory[] = $serializedEvent;
        }

        return $enrichedHistory;
    }

    private static function getAggregateDescriptionByEvent(string $eventName, array $aggregateDescriptions): ?array
    {
        foreach ($aggregateDescriptions as $description) {
            if (\array_key_exists($eventName, $description['eventApplyMap'])) {
                return $description;
            }
        }

        return null;
    }
}
