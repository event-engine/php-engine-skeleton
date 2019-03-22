<?php
declare(strict_types=1);

namespace MyService\Persistence;

use Codeliner\ArrayReader\ArrayReader;
use EventEngine\Discolight\ServiceRegistry;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\DocumentStore\Postgres\PostgresDocumentStore;
use EventEngine\EventEngine;
use EventEngine\EventStore\EventStore;
use EventEngine\Persistence\ComposedMultiModelStore;
use EventEngine\Persistence\MultiModelStore;
use EventEngine\Persistence\TransactionalConnection;
use EventEngine\Projecting\AggregateProjector;
use EventEngine\Prooph\V7\EventStore\ProophEventStore;
use EventEngine\Prooph\V7\EventStore\ProophEventStoreMessageFactory;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\EventStore as ProophV7EventStore;
use Prooph\EventStore\Pdo\PersistenceStrategy;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;

trait PersistenceServices
{
    abstract public function eventEngine(): EventEngine;

    public function pdoConnection(): \PDO
    {
        return $this->makeSingleton(\PDO::class, function () {
            $this->assertMandatoryConfigExists('pdo.dsn');
            $this->assertMandatoryConfigExists('pdo.user');
            $this->assertMandatoryConfigExists('pdo.pwd');

            return new \PDO(
                $this->config()->stringValue('pdo.dsn'),
                $this->config()->stringValue('pdo.user'),
                $this->config()->stringValue('pdo.pwd')
            );
        });
    }

    public function transactionalConnection(): TransactionalConnection
    {
        return $this->makeSingleton(TransactionalConnection::class, function () {
            return new PostgresTransactionalConnection($this->pdoConnection());
        });
    }

    public function multiModelStore(): MultiModelStore
    {
        return $this->makeSingleton(MultiModelStore::class, function () {
            return new ComposedMultiModelStore(
                $this->transactionalConnection(),
                $this->eventEngineEventStore(),
                $this->documentStore()
            );
        });
    }

    public function documentStore(): DocumentStore
    {
        return $this->makeSingleton(DocumentStore::class, function () {
            return new PostgresDocumentStore(
                $this->pdoConnection(),
                '', //No table prefix
                'CHAR(36) NOT NULL', //Use alternative docId schema, to allow uuids as well as md5 hashes
                false //Disable transaction handling, as this is controlled by the MultiModelStore
            );
        });
    }

    public function eventEngineEventStore(): EventStore
    {
        return $this->makeSingleton(EventStore::class, function () {
            return new ProophEventStore($this->proophEventStore());
        });
    }

    public function proophEventStore(): ProophV7EventStore
    {
        return $this->makeSingleton(ProophV7EventStore::class, function () {
            $eventStore = new PostgresEventStore(
                new ProophEventStoreMessageFactory(),
                $this->pdoConnection(),
                $this->proophEventStorePersistenceStrategy()
            );

            return new TransactionalActionEventEmitterEventStore(
                $eventStore,
                new ProophActionEventEmitter(TransactionalActionEventEmitterEventStore::ALL_EVENTS)
            );
        });
    }

    protected function proophEventStorePersistenceStrategy(): PersistenceStrategy
    {
        return $this->makeSingleton(PersistenceStrategy::class, function () {
            return new PersistenceStrategy\PostgresSingleStreamStrategy();
        });
    }

    public function writeModelStreamProjection(): WriteModelStreamProjection
    {
        return $this->makeSingleton(WriteModelStreamProjection::class, function () {
            return new WriteModelStreamProjection(
                $this->projectionManager(),
                $this->eventEngine()
            );
        });
    }

    public function projectionManager(): ProjectionManager
    {
        return $this->makeSingleton(ProjectionManager::class, function () {
            return new PostgresProjectionManager(
                $this->proophEventStore(),
                $this->pdoConnection()
            );
        });
    }

    public function aggregateProjector(): AggregateProjector
    {
        return $this->makeSingleton(AggregateProjector::class, function () {
            return new AggregateProjector(
                $this->documentStore(),
                $this->eventEngine()
            );
        });
    }

    abstract protected function makeSingleton(string $serviceId, callable $factory);
    abstract protected function config(): ArrayReader;
    abstract protected function assertMandatoryConfigExists(string $path): void;
}
