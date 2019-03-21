<?php
declare(strict_types=1);

namespace MyService\Persistence;

use EventEngine\EventEngine;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ReadModelProjector;

final class WriteModelStreamProjection
{
    public const NAME = 'ee_write_model_projection';

    /**
     * @var ReadModelProjector
     */
    private $projection;

    /**
     * @var bool
     */
    private $testMode;

    public function __construct(
        ProjectionManager $projectionManager,
        EventEngine $eventEngine,
        array $projectionOptions = null,
        bool $testMode = false
    ) {
        if (null === $projectionOptions) {
            $projectionOptions = [
                ReadModelProjector::OPTION_PERSIST_BLOCK_SIZE => 1,
            ];
        }

        $this->testMode = $testMode;

        $sourceStreams = [];

        foreach ($eventEngine->projectionInfo()->projections() as $projectionInfo) {
            foreach ($projectionInfo->sourceStreams()->items() as $sourceStream) {
                if ($sourceStream->isLocalService()) {
                    $sourceStreams[$sourceStream->streamName()] = null;
                }
            }
        }

        $sourceStreams = \array_keys($sourceStreams);

        $totalSourceStreams = \count($sourceStreams);

        if ($totalSourceStreams === 0) {
            return;
        }

        $this->projection = $projectionManager->createReadModelProjection(
            self::NAME,
            new ReadModelProxy($eventEngine),
            $projectionOptions
        );

        if ($totalSourceStreams === 1) {
            $this->projection->fromStream($sourceStreams[0]);
        } else {
            $this->projection->fromStreams(...$sourceStreams);
        }

        $this->projection->whenAny(function ($state, Message $event) {
            $this->readModel()->stack('handle', $this->streamName(), $event);
        });
    }

    public function run(bool $keepRunning = true): void
    {
        $this->projection->run(! $this->testMode && $keepRunning);
    }
}
