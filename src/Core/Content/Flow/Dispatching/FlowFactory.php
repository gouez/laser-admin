<?php declare(strict_types=1);

namespace Laser\Core\Content\Flow\Dispatching;

use Laser\Core\Content\Flow\Dispatching\Storer\FlowStorer;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\FlowEventAware;
use Laser\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('business-ops')]
class FlowFactory
{
    /**
     * @param FlowStorer[] $storer
     */
    public function __construct(private $storer)
    {
    }

    public function create(FlowEventAware $event): StorableFlow
    {
        $stored = $this->getStored($event);

        return $this->restore($event->getName(), $event->getContext(), $stored);
    }

    /**
     * @param array<string, mixed> $stored
     * @param array<string, mixed> $data
     */
    public function restore(string $name, Context $context, array $stored = [], array $data = []): StorableFlow
    {
        $flow = new StorableFlow($name, $context, $stored, $data);

        foreach ($this->storer as $storer) {
            $storer->restore($flow);
        }

        return $flow;
    }

    /**
     * @return array<string, mixed>
     */
    private function getStored(FlowEventAware $event): array
    {
        $stored = [];
        foreach ($this->storer as $storer) {
            $stored = $storer->store($event, $stored);
        }

        return $stored;
    }
}