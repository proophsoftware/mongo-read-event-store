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
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\Model\CollectionInfo;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\ReadOnlyEventStore;
use Prooph\EventStore\StreamName;

final class ReadOnlyMongoEventStore implements ReadOnlyEventStore
{
    /**
     * @var MongoConnection
     */
    private $mongoConnection;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var MessageConverter
     */
    private $messageConverter;

    /**
     * @var array
     */
    private $aggregateStreamNames;

    public function __construct(MongoConnection $mongoConnection, MessageFactory $messageFactory, MessageConverter $messageConverter, array $aggregateStreamNames)
    {
        $this->mongoConnection = $mongoConnection;
        $this->messageFactory = $messageFactory;
        $this->messageConverter = $messageConverter;
        $this->aggregateStreamNames = $aggregateStreamNames;
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        return [];
    }

    public function hasStream(StreamName $streamName): bool
    {
        $collections = $this->mongoConnection->client()->selectDatabase($this->mongoConnection->dbName())->listCollections();

        $streamName = $streamName->toString();
        /** @var CollectionInfo $colInfo */
        foreach ($collections as $colInfo) {
            if ($colInfo->getName() === $streamName) {
                return true;
            }
        }

        return false;
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        $collection = $this->getCollectionByStreamName($streamName);

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $query = $this->buildQuery($metadataMatcher);

        $options = [
            'sort' => ['created_at' => 1],
        ];

        $options['skip'] = $fromNumber - 1;

        if ($count) {
            $options['limit'] = $count;
        }

        $doc = $collection->findOne($query, $options);

        if (! $doc) {
            return new \ArrayIterator([]);
        }

        $cursor = $collection->find($query, $options);

        return $this->mapCursor($cursor, function (array $event) {
            return $this->eventDataToMessage($event);
        });
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = null,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        $collection = $this->getCollectionByStreamName($streamName);

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $query = $this->buildQuery($metadataMatcher);

        $options = [
            'sort' => ['created_at' => -1],
        ];

        $options['skip'] = $fromNumber - 1;

        if ($count) {
            $options['limit'] = $count;
        }

        $doc = $collection->findOne($query, $options);

        if (! $doc) {
            return new \ArrayIterator([]);
        }

        $cursor = $collection->find($query, $options);

        return $this->mapCursor($cursor, function (array $event) {
            return $this->eventDataToMessage($event);
        });
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
        $collections = $this->mongoConnection->client()->selectDatabase($this->mongoConnection->dbName())->listCollections();
        $names = [];

        /** @var CollectionInfo $colInfo */
        foreach ($collections as $i => $colInfo) {
            if ($offset <= $i && count($names) < $limit) {
                if ($filter && $filter !== $colInfo->getName()) {
                    continue;
                }

                $names[] = new StreamName($colInfo->getName());
            }
        }

        return $names;
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
        $collections = $this->mongoConnection->client()->selectDatabase($this->mongoConnection->dbName())->listCollections();
        $names = [];

        /** @var CollectionInfo $colInfo */
        foreach ($collections as $i => $colInfo) {
            if ($offset <= $i && count($names) < $limit) {
                if (preg_match('/' . $filter . '/', $colInfo->getName())) {
                    $names[] = new StreamName($colInfo->getName());
                }
            }
        }

        return $names;
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        return [];
    }

    private function eventDataToMessage(array $eventData): Message
    {
        $eventData['created_at'] = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.u',
            $eventData['created_at'],
            new \DateTimeZone('UTC')
        );

        $eventData['uuid'] = $eventData['_id'];
        unset($eventData['_id']);

        return $this->messageFactory->createMessageFromArray($eventData['event_name'], $eventData);
    }

    private function buildQuery(MetadataMatcher $matcher): array
    {
        $query = [];

        foreach ($matcher->data() as $match) {
            $field = $match['field'];
            $operator = $match['operator']->getValue();
            $value = $match['value'];

            switch ($operator) {
                case Operator::EQUALS:
                    $operator = '$eq';
                    break;
                case Operator::GREATER_THAN:
                    $operator = '$gt';
                    break;
                case Operator::GREATER_THAN_EQUALS:
                    $operator = '$gte';
                    break;
                case Operator::LOWER_THAN:
                    $operator = '$lt';
                    break;
                case Operator::LOWER_THAN_EQUALS:
                    $operator = '$lte';
                    break;
                case Operator::NOT_EQUALS:
                    $operator = '$ne';
                    break;
            }

            switch ($field) {
                case '_aggregate_version':
                    $field = 'version';
                    break;
                case '_agregate_type':
                    $field = 'aggregate_type';
                    break;
                case '_aggregate_id':
                    $field = 'aggregate_id';
                    break;
                default:
                    $field = 'metadata.' . $field;
            }

            $query[$field] = [$operator => $value];
        }

        return $query;
    }

    private function getCollectionByStreamName(StreamName $streamName): Collection
    {
        $streamName = $streamName->toString();

        $collection = $this->mongoConnection->selectCollection($streamName);

        return $collection;
    }

    private function mapCursor(Cursor $cursor, callable $callback): \IteratorIterator
    {
        return new class($cursor, $callback) extends \IteratorIterator {
            /**
             * The function to be apply on all InnerIterator element
             *
             * @var callable
             */
            private $callable;

            private $currentKey;

            private $currentVal;

            /**
             * The Constructor
             *
             * @param Cursor $cursor
             * @param callable $callable
             */
            public function __construct(Cursor $cursor, callable $callable)
            {
                parent::__construct($cursor);
                $this->callable = $callable;
            }

            public function valid(): bool
            {
                return ! $this->getInnerIterator()->isDead();
            }

            /**
             * Get the value of the current element
             */
            public function current()
            {
                $callback = $this->callable;

                return $callback(parent::current(), parent::key());
            }
        };
    }
}
