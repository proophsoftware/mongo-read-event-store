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
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresMandatoryOptions;
use Prooph\ReadOnlyMongoEventStore\DecoratedReadOnlyEventStore;
use Psr\Container\ContainerInterface;

final class DecoratedReadOnlyEventStoreFactory implements RequiresConfig, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    public function __invoke(ContainerInterface $container)
    {
        $options = $this->options($container->get('config'));

        $innerEventStore = $container->get($options['decorated_store']);

        return new DecoratedReadOnlyEventStore($innerEventStore);
    }

    /**
     * Returns the depth of the configuration array as a list. Can also be an empty array. For instance, the structure
     * of the dimensions() method would be an array like
     *
     * <code>
     *   return ['prooph', 'service_bus', 'command_bus'];
     * </code>
     *
     * @return iterable
     */
    public function dimensions(): iterable
    {
        return ['prooph', 'read_only_event_store'];
    }

    /**
     * Returns a list of mandatory options which must be available
     *
     * @return iterable List with mandatory options, can be nested
     */
    public function mandatoryOptions(): iterable
    {
        return ['decorated_store'];
    }
}
