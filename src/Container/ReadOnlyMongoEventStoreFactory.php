<?php
/**
 * This file is part of the proophsoftware/mongo-read-event-store.
 * (c) 2017 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Prooph\ReadOnlyMongoEventStore\Container;

use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresMandatoryOptions;
use MongoDB\Client;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ReadOnlyMongoEventStore\Event\ProophVersionUpcastingMessageFactory;
use Prooph\ReadOnlyMongoEventStore\MongoConnection;
use Prooph\ReadOnlyMongoEventStore\ReadOnlyMongoEventStore;
use Psr\Container\ContainerInterface;

final class ReadOnlyMongoEventStoreFactory implements RequiresConfig, RequiresMandatoryOptions, ProvidesDefaultOptions
{
    use ConfigurationTrait;

    public function __invoke(ContainerInterface $container)
    {
        $options = $this->options($container->get('config'));

        $connection = new MongoConnection(
            new Client(
                $options['connection']['server'],
                $options['connection']['uri_options'],
                $options['connection']['driver_options']
            ),
            $options['connection']['db_name']
        );

        $messageFactory = isset($options['message_factory'])?
            $container->get($options['message_factory'])
            : new ProophVersionUpcastingMessageFactory();

        $messageConverter = isset($options['message_converter'])?
            $container->get($options['message_converter'])
            : new NoOpMessageConverter();

        $aggregateStreamNames = $options['aggregate_stream_names'] ?? [];

        return new ReadOnlyMongoEventStore($connection, $messageFactory, $messageConverter, $aggregateStreamNames);
    }

    /**
     * @inheritdoc \Interop\Config\RequiresConfig::dimensions
     */
    public function dimensions(): iterable
    {
        return ['prooph', 'read_only_event_store', 'mongodb'];
    }

    /**
     * Returns a list of default options, which are merged in \Interop\Config\RequiresConfig::options()
     *
     * @return iterable List with default options and values, can be nested
     */
    public function defaultOptions(): iterable
    {
        return [
            'connection' => [
                'server' => 'mongodb://127.0.0.1/',
                'uri_options' => [],
                'driver_options' => []
            ],
        ];
    }

    /**
     * Returns a list of mandatory options which must be available
     *
     * @return iterable List with mandatory options, can be nested
     */
    public function mandatoryOptions(): iterable
    {
        return [
            'connection' => [
                'server',
                'db_name'
            ],
        ];
    }
}
