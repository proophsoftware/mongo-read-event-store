<?php
/**
 * This file is part of the proophsoftware/mongo-read-event-store.
 * (c) 2017 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Prooph\ReadOnlyMongoEventStore;

use Iterator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\ReadOnlyEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

final class DecoratedReadOnlyEventStore implements EventStore
{
    private const BAD_METHOD_CALL = 'Operation %s is not allowed. Event Store is read only!';
    /**
     * @var ReadOnlyEventStore
     */
    private $readOnlyEventStore;

    public function __construct(ReadOnlyEventStore $readOnlyEventStore)
    {
        $this->readOnlyEventStore = $readOnlyEventStore;
    }

    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void
    {
        throw new \BadMethodCallException(sprintf(self::BAD_METHOD_CALL, __METHOD__));
    }

    public function create(Stream $stream): void
    {
        throw new \BadMethodCallException(sprintf(self::BAD_METHOD_CALL, __METHOD__));
    }

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        throw new \BadMethodCallException(sprintf(self::BAD_METHOD_CALL, __METHOD__));
    }

    public function delete(StreamName $streamName): void
    {
        throw new \BadMethodCallException(sprintf(self::BAD_METHOD_CALL, __METHOD__));
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        return $this->readOnlyEventStore->fetchStreamMetadata($streamName);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->readOnlyEventStore->hasStream($streamName);
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        return $this->readOnlyEventStore->load($streamName, $fromNumber, $count, $metadataMatcher);
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = null,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        return $this->readOnlyEventStore->loadReverse($streamName, $fromNumber, $count, $metadataMatcher);
    }

    /**
     * @return StreamName[]
     */
    public function fetchStreamNames(
        ?string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->readOnlyEventStore->fetchStreamNames($filter, $metadataMatcher, $limit, $offset);
    }

    /**
     * @return StreamName[]
     */
    public function fetchStreamNamesRegex(
        string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->readOnlyEventStore->fetchStreamNamesRegex($filter, $metadataMatcher, $limit, $offset);
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->readOnlyEventStore->fetchCategoryNames($filter, $limit, $offset);
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->readOnlyEventStore->fetchCategoryNamesRegex($filter, $limit, $offset);
    }
}
