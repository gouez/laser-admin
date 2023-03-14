<?php declare(strict_types=1);

namespace Laser\Core\Content\Newsletter\Event;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\NestedEvent;
use Laser\Core\Framework\Log\Package;

#[Package('customer-order')]
class NewsletterRecipientIndexerEvent extends NestedEvent
{
    public function __construct(
        private readonly array $ids,
        private readonly Context $context,
        private readonly array $skip = []
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

    public function getSkip(): array
    {
        return $this->skip;
    }
}