<?php declare(strict_types=1);

namespace Laser\Core\Framework\Update\Event;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;

#[Package('system-settings')]
class UpdatePostFinishEvent extends UpdateEvent
{
    private string $postUpdateMessage = '';

    public function __construct(
        Context $context,
        private readonly string $oldVersion,
        private readonly string $newVersion
    ) {
        parent::__construct($context);
    }

    public function getOldVersion(): string
    {
        return $this->oldVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }

    public function getPostUpdateMessage(): string
    {
        return $this->postUpdateMessage;
    }

    public function appendPostUpdateMessage(string $postUpdateMessage): void
    {
        $this->postUpdateMessage .= \PHP_EOL . $postUpdateMessage . \PHP_EOL;
    }
}
