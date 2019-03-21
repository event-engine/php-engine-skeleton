<?php
declare(strict_types=1);

namespace MyService\System;

use Codeliner\ArrayReader\ArrayReader;
use EventEngine\Discolight\ServiceRegistry;
use EventEngine\Logger\LogEngine;
use EventEngine\Logger\SimpleMessageEngine;
use EventEngine\Messaging\Message;
use EventEngine\Prooph\V7\EventStore\GenericProophEvent;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MyService\System\Api\SystemQuery;
use MyService\System\Api\SystemType;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Message\HumusAmqp\AmqpMessageProducer;
use Psr\Log\LoggerInterface;

trait SystemServices
{
    public function systemDescriptions(): array
    {
        return [
            SystemType::class,
            SystemQuery::class,
        ];
    }

    public function healthCheckResolver(): HealthCheckResolver
    {
        return $this->makeSingleton(HealthCheckResolver::class, function () {
            return new HealthCheckResolver();
        });
    }

    public function logger(): LoggerInterface
    {
        return $this->makeSingleton(LoggerInterface::class, function () {
            $streamHandler = new StreamHandler('php://stderr');

            return new Logger('EventEngine', [$streamHandler]);
        });
    }

    public function logEngine(): LogEngine
    {
        return new SimpleMessageEngine($this->logger());
    }

    public function uiExchange(): UiExchange
    {
        return $this->makeSingleton(UiExchange::class, function () {
            $this->assertMandatoryConfigExists('rabbit.connection');

            $connection = new \Humus\Amqp\Driver\AmqpExtension\Connection(
                $this->config()->arrayValue('rabbit.connection')
            );

            $connection->connect();

            $channel = $connection->newChannel();

            $exchange = $channel->newExchange();

            $exchange->setName($this->config()->stringValue('rabbit.ui_exchange', 'ui-exchange'));

            $exchange->setType('fanout');

            $humusProducer = new \Humus\Amqp\JsonProducer($exchange);

            $messageProducer = new \Prooph\ServiceBus\Message\HumusAmqp\AmqpMessageProducer(
                $humusProducer,
                new NoOpMessageConverter()
            );

            return new class($messageProducer) implements UiExchange {
                private $producer;
                public function __construct(AmqpMessageProducer $messageProducer)
                {
                    $this->producer = $messageProducer;
                }

                public function __invoke(Message $event): void
                {
                    $this->producer->__invoke(GenericProophEvent::fromArray([
                        'uuid' => $event->uuid()->toString(),
                        'message_name' => $event->messageName(),
                        'payload' => $event->payload(),
                        'metadata' => $event->metadata(),
                        'created_at' => $event->createdAt()
                    ]));
                }
            };
        });
    }

    abstract protected function makeSingleton(string $serviceId, callable $factory);
    abstract protected function config(): ArrayReader;
    abstract protected function assertMandatoryConfigExists(string $path): void;
}
