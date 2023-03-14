<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\SalesChannel\Suggest;

use Laser\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Laser\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Laser\Core\Content\Product\Events\ProductSuggestResultEvent;
use Laser\Core\Content\Product\ProductEvents;
use Laser\Core\Content\Product\SalesChannel\Listing\ProductListingLoader;
use Laser\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Laser\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Laser\Core\Content\Product\SearchKeyword\ProductSearchBuilderInterface;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('system-settings')]
class ProductSuggestRoute extends AbstractProductSuggestRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductSearchBuilderInterface $searchBuilder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProductListingLoader $productListingLoader
    ) {
    }

    public function getDecorated(): AbstractProductSuggestRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/search-suggest', name: 'store-api.search.suggest', methods: ['POST'], defaults: ['_entity' => 'product'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSuggestRouteResponse
    {
        if (!$request->get('search')) {
            throw new MissingRequestParameterException('search');
        }

        $criteria->addFilter(
            new ProductAvailableFilter($context->getSalesChannel()->getId(), ProductVisibilityDefinition::VISIBILITY_SEARCH)
        );

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $this->searchBuilder->build($request, $criteria, $context);

        $this->eventDispatcher->dispatch(
            new ProductSuggestCriteriaEvent($request, $criteria, $context),
            ProductEvents::PRODUCT_SUGGEST_CRITERIA
        );

        $result = $this->productListingLoader->load($criteria, $context);

        $result = ProductListingResult::createFrom($result);

        $this->eventDispatcher->dispatch(
            new ProductSuggestResultEvent($request, $result, $context),
            ProductEvents::PRODUCT_SUGGEST_RESULT
        );

        return new ProductSuggestRouteResponse($result);
    }
}