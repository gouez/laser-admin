<?php declare(strict_types=1);

namespace Laser\Core\Content\Flow\Events;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\NestedEvent;
use Laser\Core\Framework\Log\Package;

#[Package('business-ops')]
class FlowIndexerEvent extends NestedEvent
{
    public function __construct(
        private readonly array $ids,
        private readonly Context $context
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getIds(): array
    {
        return $this->ids;
    }
}
