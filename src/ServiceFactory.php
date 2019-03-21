<?php

namespace MyService;

use Codeliner\ArrayReader\ArrayReader;
use EventEngine\Data\ImmutableRecordDataConverter;
use EventEngine\Discolight\ServiceRegistry;
use EventEngine\EventEngine;
use EventEngine\JsonSchema\OpisJsonSchema;
use EventEngine\Runtime\Flavour;
use EventEngine\Runtime\PrototypingFlavour;
use MyService\Domain\DomainServices;
use MyService\Http\HttpServices;
use MyService\Persistence\PersistenceServices;
use MyService\System\SystemServices;
use Psr\Container\ContainerInterface;

final class ServiceFactory
{
    use ServiceRegistry,
        SystemServices,
        HttpServices,
        PersistenceServices,
        DomainServices;

    /**
     * @var ArrayReader
     */
    private $config;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(array $appConfig)
    {
        $this->config = new ArrayReader($appConfig);
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function eventEngine($notInitialized = false): EventEngine
    {
        if($notInitialized) {
            $eventEngine = new EventEngine(new OpisJsonSchema());

            foreach ($this->eventEngineDescriptions() as $description) {
                $eventEngine->load($description);
            }

            return $eventEngine;
        }

        $this->assertContainerIsset();

        return $this->makeSingleton(EventEngine::class, function () {
            //@TODO Load from cached config, if it exists
            $eventEngine = new EventEngine(new OpisJsonSchema());

            foreach ($this->eventEngineDescriptions() as $description) {
                $eventEngine->load($description);
            }

            $eventEngine->initialize(
                $this->flavour(),
                $this->multiModelStore(),
                $this->logEngine(),
                $this->container
            );

            return $eventEngine;
        });
    }

    public function flavour(): Flavour
    {
        return $this->makeSingleton(Flavour::class, function () {
            return new PrototypingFlavour(new ImmutableRecordDataConverter());
        });
    }

    private function assertContainerIsset(): void
    {
        if(null === $this->container) {
            throw new \RuntimeException("Main container is not set. Use " . __CLASS__ . "::setContainer() to set it.");
        }
    }

    private function eventEngineDescriptions(): array
    {
        return \array_merge($this->domainDescriptions(), $this->systemDescriptions());
    }

    protected function assertMandatoryConfigExists(string $path): void
    {
        if(null === $this->config->mixedValue($path)) {
            throw  new \RuntimeException("Missing application config for $path");
        }
    }

    protected function config(): ArrayReader
    {
        return $this->config;
    }
}
