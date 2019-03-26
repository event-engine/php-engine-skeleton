<?php
declare(strict_types=1);

namespace MyServiceTest;

use EventEngine\Messaging\GenericEvent;
use EventEngine\Messaging\Message;
use EventEngine\Schema\TypeSchemaMap;
use MyServiceTest\Helper\AggregateTestHistoryEventEnricher;

class IntegrationTestCase extends TestCaseAbstract
{
    /**
     * @var TypeSchemaMap
     */
    private $typeSchemaMap;

    protected function setUpDatabase(array $fixtures)
    {
        $createdStreams = [];
        $aggregates = [];

        foreach ($this->compiledDescription['aggregateDescriptions'] as $aggregateType => $description) {
            $aggregateStream = $description['aggregateStream'];

            if(!in_array($aggregateStream, $createdStreams)) {
                $this->eventStore->createStream($aggregateStream);
                $createdStreams[] = $aggregateStream;

                if(\array_key_exists($aggregateStream, $fixtures)) {
                    $history = AggregateTestHistoryEventEnricher::enrichHistory(
                        $fixtures[$aggregateStream],
                        $this->compiledDescription['aggregateDescriptions'],
                        $this->eventEngine->flavour()
                    );

                    $this->eventStore->appendTo($aggregateStream, ...$history);

                    foreach ($history as $event) {
                        /** @var Message $event */
                        $arType = $event->getMetaOrDefault(GenericEvent::META_AGGREGATE_TYPE, null);
                        $arId = $event->getMetaOrDefault(GenericEvent::META_AGGREGATE_ID, null);

                        if($arType && $arId) {
                            $aggregates[$arType][$arId] = true;
                        }
                    }
                }
            }

            $collection = $description['aggregateCollection'];

            if(!$this->documentStore->hasCollection($collection)) {
                $this->documentStore->addCollection($collection);

                if(\array_key_exists($collection, $fixtures)) {
                    foreach ($fixtures[$collection] ?? [] as $docId => $doc) {
                        $this->documentStore->addDoc($collection, $docId, $doc);
                    }
                }
            }
        }

        foreach ($aggregates as $aggregateType => $ids) {
            foreach ($ids as $arId) {
                $this->eventEngine->rebuildAggregateState($aggregateType, $arId);
            }
        }
    }

    protected function initializeTypes()
    {
        $this->typeSchemaMap = new TypeSchemaMap();

        foreach ($this->compiledDescription['responseTypes'] ?? [] as $typeName => $typeSchema) {
            $this->typeSchemaMap->add($typeName, $this->eventEngine->schema()->buildResponseTypeSchemaFromArray($typeName, $typeSchema));
        }

        foreach ($this->compiledDescription['inputTypes'] ?? [] as $typeName => $typeSchema) {
            $this->typeSchemaMap->add($typeName, $this->eventEngine->schema()->buildResponseTypeSchemaFromArray($typeName, $typeSchema));
        }
    }

    protected function processEventQueueWhileNotEmpty(): void
    {
        $rounds = 0;

        while (\count($this->eventQueue->queuedEvents())) {
            $this->eventQueue->process();
            $rounds++;

            if($rounds > 100) {
                throw new \OutOfBoundsException("Stopping infinite loop in: " . __METHOD__);
            }
        }
    }

    protected function assertResponseType(string $query, $response): void
    {
        if(is_object($response) && method_exists($response, 'toArray')) {
            $response = $response->toArray();
        }

        $responseType = $this->compiledDescription['compiledQueryDescriptions'][$query]['returnType'] ?? null;

        $this->assertNotNull($responseType, "Query $query has no response type!");

        $this->eventEngine->schema()->assertPayload(
            $query . ' response',
            $response,
            $this->eventEngine->schema()->buildResponseTypeSchemaFromArray($query, $responseType),
            $this->typeSchemaMap
        );
    }
}

