<?php declare(strict_types=1);

namespace Laser\Core\Content\Sitemap\ScheduledTask;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('sales-channel')]
class SitemapMessage implements AsyncMessageInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ?string $lastSalesChannelId,
        private readonly ?string $lastLanguageId,
        private readonly ?string $lastProvider,
        private readonly ?int $nextOffset,
        private readonly bool $finished
    ) {
    }

    public function getLastSalesChannelId(): ?string
    {
        return $this->lastSalesChannelId;
    }

    public function getLastLanguageId(): ?string
    {
        return $this->lastLanguageId;
    }

    public function getLastProvider(): ?string
    {
        return $this->lastProvider;
    }

    public function getNextOffset(): ?int
    {
        return $this->nextOffset;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }
}