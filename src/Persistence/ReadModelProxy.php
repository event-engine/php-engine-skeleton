<?php
declare(strict_types=1);

namespace MyService\Persistence;

use EventEngine\EventEngine;
use EventEngine\Messaging\GenericEvent;
use EventEngine\Prooph\V7\EventStore\GenericProophEvent;
use EventEngine\Util\VariableType;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Projection\AbstractReadModel;

final class ReadModelProxy extends AbstractReadModel
{
    /**
     * @var EventEngine
     */
    private $eventEngine;

    private $initialized = false;

    public function __construct(EventEngine $eventEngine)
    {
        $this->eventEngine = $eventEngine;
    }

    public function handle(string $streamName, Message $event): void
    {
        if(!$event instanceof GenericProophEvent) {
            throw new \InvalidArgumentException(__METHOD__ . ' expects a ' . GenericProophEvent::class . '. Got ' . VariableType::determine($event));
        }

        $this->eventEngine->runAllProjections($streamName, GenericEvent::fromArray($event->toArray()));
    }

    public function init(): void
    {
        $this->eventEngine->setUpAllProjections();
        $this->initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function reset(): void
    {
        $this->delete();
    }

    public function delete(): void
    {
        if (! $this->isInitialized()) {
            $this->init();
        }

        $this->eventEngine->deleteAllProjections();

        $this->initialized = false;
    }
}
