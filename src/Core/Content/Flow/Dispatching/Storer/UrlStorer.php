<?php declare(strict_types=1);

namespace Laser\Core\Content\Flow\Dispatching\Storer;

use Laser\Core\Content\Flow\Dispatching\Aware\UrlAware;
use Laser\Core\Content\Flow\Dispatching\StorableFlow;
use Laser\Core\Framework\Event\FlowEventAware;
use Laser\Core\Framework\Log\Package;

#[Package('business-ops')]
class UrlStorer extends FlowStorer
{
    /**
     * @param array<string, mixed> $stored
     *
     * @return array<string, mixed>
     */
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof UrlAware || isset($stored[UrlAware::URL])) {
            return $stored;
        }

        $stored[UrlAware::URL] = $event->getUrl();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(UrlAware::URL)) {
            return;
        }

        $storable->setData(UrlAware::URL, $storable->getStore(UrlAware::URL));
    }
}
