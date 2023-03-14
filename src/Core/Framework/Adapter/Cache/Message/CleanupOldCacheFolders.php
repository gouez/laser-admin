<?php declare(strict_types=1);

namespace Laser\Core\Framework\Adapter\Cache\Message;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('core')]
class CleanupOldCacheFolders implements AsyncMessageInterface
{
}
