<?php
/**
 * This file is part of the proophsoftware/mongo-read-event-store.
 * (c) 2017 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Prooph\ReadOnlyMongoEventStore\Event;

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageFactory;
use Ramsey\Uuid\Uuid;

final class ProophVersionUpcastingMessageFactory implements MessageFactory
{
    /**
     * {@inheritdoc}
     */
    public function createMessageFromArray(string $messageName, array $messageData): Message
    {
        if (! isset($messageData['message_name'])) {
            $messageData['message_name'] = $messageName;
        }

        if (! isset($messageData['uuid'])) {
            $messageData['uuid'] = Uuid::uuid4();
        }

        if (! isset($messageData['created_at'])) {
            $time = (string) microtime(true);
            if (false === strpos($time, '.')) {
                $time .= '.0000';
            }
            $messageData['created_at'] = \DateTimeImmutable::createFromFormat('U.u', $time);
        }

        if (! isset($messageData['metadata'])) {
            $messageData['metadata'] = [];
        }

        //Upcast to prooph/event-store v7 format
        if (isset($messageData['version'])) {
            $messageData['metadata']['_aggregate_version'] = $messageData['version'];
            unset($messageData['version']);
        }

        if (isset($messageData['aggregate_id'])) {
            $messageData['metadata']['_aggregate_id'] = $messageData['aggregate_id'];
            unset($messageData['aggregate_id']);
        }

        if (isset($messageData['aggregate_type'])) {
            $messageData['metadata']['_aggregate_type'] = $messageData['aggregate_type'];
            unset($messageData['aggregate_type']);
        }

        return GenericEvent::fromArray($messageData);
    }
}
