<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\Events;

use Laser\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class ProductListingRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
    public function __construct(
        array $parts,
        protected string $categoryId,
        Request $request,
        SalesChannelContext $context,
        Criteria $criteria
    ) {
        parent::__construct($parts, $request, $context, $criteria);
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }
}