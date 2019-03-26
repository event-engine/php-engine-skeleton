<?php
declare(strict_types=1);

namespace MyServiceTest;

use EventEngine\DocumentStore\DocumentStore;
use EventEngine\EventEngine;
use EventEngine\EventStore\EventStore;
use EventEngine\Logger\DevNull;
use EventEngine\Logger\SimpleMessageEngine;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Persistence\InMemoryConnection;
use EventEngine\Prooph\V7\EventStore\InMemoryMultiModelStore;
use EventEngine\Util\MessageTuple;
use MyService\ServiceFactory;
use MyServiceTest\Mock\EventQueueMock;
use MyServiceTest\Mock\MockContainer;
use PHPUnit\Framework\TestCase;

class TestCaseAbstract extends TestCase
{
    /**
     * @var EventEngine
     */
    protected $eventEngine;

    /**
     * @var EventQueueMock
     */
    protected $eventQueue;

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var DocumentStore
     */
    protected $documentStore;

    /**
     * @var array
     */
    protected $compiledDescription;

    protected function setUpEventEngine(array $mocks = []): void
    {
        $appServiceFactory = new ServiceFactory([]);
        $this->eventEngine = $appServiceFactory->eventEngine(true);
        $this->compiledDescription = $this->eventEngine->compileCacheableConfig();

        $multiModelStore = InMemoryMultiModelStore::fromConnection(new InMemoryConnection());

        $this->eventStore = $multiModelStore;
        $this->documentStore = $multiModelStore;

        $this->eventQueue = new class($this->eventEngine) implements MessageProducer, EventQueueMock {

            private $queuedEvents = [];

            /**
             * @var EventEngine
             */
            private $eventEngine;

            public function __construct(EventEngine $eventEngine)
            {
                $this->eventEngine = $eventEngine;
            }

            /**
             * @param Message $message
             * @return mixed|null In case of a query a result is returned otherwise null
             */
            public function produce(Message $message)
            {
                $this->queuedEvents[] = $message;
            }

            /**
             * @return \EventEngine\Messaging\Event
             */
            public function queuedEvents(): array
            {
                return $this->queuedEvents;
            }

            public function process(): void
            {
                $events = $this->queuedEvents;
                $this->queuedEvents = [];
                foreach ($events as $event) $this->eventEngine->dispatch($event);
            }
        };

        $this->eventEngine->initialize(
            $appServiceFactory->flavour(),
            $this->eventStore,
            new SimpleMessageEngine(new DevNull()),
            new MockContainer($mocks),
            $this->documentStore,
            $this->eventQueue
        );

        $this->eventEngine->bootstrap(EventEngine::ENV_TEST, true);
    }

    protected function makeCommandFromTuple(array $messageTuple): Message
    {
        [$messageName, $payload, $metadata] = MessageTuple::normalize($messageTuple);

        return $this->makeCommand($messageName, $payload, $metadata);
    }

    protected function makeCommand(string $messageName, array $payload = [], array $metadata = []): Message
    {
        return $this->makeMessage($messageName, $payload, $metadata);
    }

    protected function makeQueryFromTuple(array $messageTuple): Message
    {
        [$messageName, $payload, $metadata] = MessageTuple::normalize($messageTuple);
        return $this->makeQuery($messageName, $payload, $metadata);
    }

    protected function makeQuery(string $messageName, array $payload = [], array $metadata = []): Message
    {
        return $this->makeMessage($messageName, $payload, $metadata);
    }

    protected function makeEventFromTuple(array $eventTuple): Message
    {
        [$messageName, $payload, $metadata] = MessageTuple::normalize($eventTuple);

        return $this->makeEvent($messageName, $payload, $metadata);
    }

    protected function makeEvent(string $eventName, array $payload = [], array $metadata = []): Message
    {
        return $this->makeMessage($eventName, $payload, $metadata);
    }

    protected function makeMessage(string $messageName, array $payload = [], array $metadata = []): Message
    {
        return $this->eventEngine->messageFactory()->createMessageFromArray($messageName, [
            'payload' => $payload,
            'metadata' => $metadata
        ]);
    }

    protected function collectNewEvents(\Generator $arFunc): array
    {
        return \iterator_to_array($arFunc);
    }

    protected function assertRecordedEvent(string $eventName, array $payload, array $events, $assertNotRecorded = false): void
    {
        $isRecorded = false;
        foreach ($events as $evt) {
            if($evt === null) {
                continue;
            }
            [$evtName, $evtPayload] = $evt;
            if($eventName === $evtName) {
                $isRecorded = true;
                if(!$assertNotRecorded) {
                    $this->assertEquals($payload, $evtPayload, "Payload of recorded event $evtName does not match with expected payload.");
                }
            }
        }
        if($assertNotRecorded) {
            $this->assertFalse($isRecorded, "Event $eventName is recorded");
        } else {
            $this->assertTrue($isRecorded, "Event $eventName is not recorded");
        }
    }
    protected function assertNotRecordedEvent(string $eventName, array $events): void
    {
        $this->assertRecordedEvent($eventName, [], $events, true);
    }
}
