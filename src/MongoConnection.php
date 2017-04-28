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

use MongoDB\Client;
use MongoDB\Collection;

class MongoConnection
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $dbName;

    public function __construct(Client $client, string $dbName)
    {
        $this->client = $client;
        $this->dbName = $dbName;
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function dbName(): string
    {
        return $this->dbName;
    }

    public function selectCollection(string $collectionName, array $options = []): Collection
    {
        return $this->client->selectCollection($this->dbName, $collectionName, $options);
    }

    public function replaceCollection(string $collectionName, string $withCollection)
    {
        $adminDb = $this->client->admin;

        $cursor = $adminDb->command([
            'renameCollection' => $this->dbName . '.' . $withCollection,
            'to' => $this->dbName . '.' . $collectionName,
            'dropTarget' => true,
        ]);

        return current($cursor->toArray());
    }
}
