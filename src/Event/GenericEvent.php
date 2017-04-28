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

use Prooph\Common\Messaging\DomainMessage;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\PayloadTrait;

final class GenericEvent extends DomainMessage
{
    use PayloadTrait;

    public function messageType(): string
    {
        return Message::TYPE_EVENT;
    }
}
