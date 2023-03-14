<?php declare(strict_types=1);

namespace Laser\Core\Content\Media\Pathname\PathnameStrategy;

use Laser\Core\Content\Media\Exception\StrategyNotFoundException;
use Laser\Core\Framework\Log\Package;

#[Package('content')]
class StrategyFactory
{
    /**
     * @internal
     *
     * @param PathnameStrategyInterface[] $strategies
     */
    public function __construct(private readonly iterable $strategies)
    {
    }

    public function factory(string $strategyName): PathnameStrategyInterface
    {
        return $this->findStrategyByName($strategyName);
    }

    /**
     * @throws StrategyNotFoundException
     */
    private function findStrategyByName(string $strategyName): PathnameStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->getName() === $strategyName) {
                return $strategy;
            }
        }

        throw new StrategyNotFoundException($strategyName);
    }
}
