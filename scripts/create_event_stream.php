<?php
declare(strict_types=1);

namespace EventEngine;

use ArrayIterator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

chdir(dirname(__DIR__));

require_once 'vendor/autoload.php';

$container = require 'config/container.php';

/** @var EventStore $eventStore */
$eventStore = $container->get(EventStore::class);

$eventStore->create(new Stream(new StreamName('event_stream'), new ArrayIterator()));

echo "done.\n";
