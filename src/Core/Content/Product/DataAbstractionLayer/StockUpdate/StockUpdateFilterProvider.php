<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\DataAbstractionLayer\StockUpdate;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;

/**
 * @internal In order to manipulate the filter process, provide your own tagged AbstractStockUpdateFilter
 */
#[Package('core')]
final class StockUpdateFilterProvider
{
    /**
     * @internal
     *
     * @param AbstractStockUpdateFilter[] $filters
     */
    public function __construct(private readonly iterable $filters)
    {
    }

    /**
     * @param list<string> $ids
     *
     * @return list<string>
     */
    public function filterProductIdsForStockUpdates(array $ids, Context $context): array
    {
        foreach ($this->filters as $filter) {
            $ids = $filter->filter($ids, $context);
        }

        return $ids;
    }
}
