# prooph MongoDb read-only event-store

## Installation

```bash
composer require proophsoftware/mongo-read-event-store
```

## Purpose

The package provides a read-only mongodb event-store that connects to a prooph/event-store v6 mongodb.
Fetched events are upcasted on-the-fly so that they fit into the message format defined by prooph/common v4 and up.
The read-only mongodb event-store can be used to migrate v6 mongodb event streams to prooph/event-store v7 event-streams
using a stream-to-stream projection. 

Furthermore, you can use the read-only mongodb event-store within the event-store-http-api
project to read your v6 event streams via HTTP.

## Limitations

With prooph/event-store v7 a new event stream position was introduced which is a sequence.
This new position is addressed in the `EventStore::load` and `EventStore::loadReverse` methods, but a v6 stream does not
have such a position. Therefor, we fall back to sort by `created_at` and use `$fromNumber - 1` as skip option.
This emulates iterating an event stream but may return a different event order as they were recorded due to same timestamp.

### Event Store HTTP API

To use the read-only mongodb event store with the event-store-http-api you need to wrap it with the `DecoratedReadOnlyEventStore`
ship with this package because the http api package requires a full event store implementation.
This means that you cannot use write actions of the http api.

## Indexing

To efficiently sort events by `created_at` you should add an index to your stream collections:

```php
$collection->createIndex([
    'created_at' => 1
], ['name' => 'prooph_rom_sort']);
```
