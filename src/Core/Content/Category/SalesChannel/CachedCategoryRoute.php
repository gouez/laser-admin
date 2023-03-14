<?php declare(strict_types=1);

namespace Laser\Core\Content\Category\SalesChannel;

use Laser\Core\Content\Category\Event\CategoryRouteCacheKeyEvent;
use Laser\Core\Content\Category\Event\CategoryRouteCacheTagsEvent;
use Laser\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Laser\Core\Content\Cms\SalesChannel\Struct\ProductBoxStruct;
use Laser\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Laser\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Laser\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Laser\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Profiling\Profiler;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('content')]
class CachedCategoryRoute extends AbstractCategoryRoute
{
    /**
     * @internal
     *
     * @param AbstractCacheTracer<CategoryRouteResponse> $tracer
     * @param array<string> $states
     */
    public function __construct(
        private readonly AbstractCategoryRoute $decorated,
        private readonly CacheInterface $cache,
        private readonly EntityCacheKeyGenerator $generator,
        private readonly AbstractCacheTracer $tracer,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly array $states
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'category-route-' . $id;
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/category/{navigationId}', name: 'store-api.category.detail', methods: ['GET', 'POST'])]
    public function load(string $navigationId, Request $request, SalesChannelContext $context): CategoryRouteResponse
    {
        return Profiler::trace('category-route', function () use ($navigationId, $request, $context) {
            if ($context->hasState(...$this->states)) {
                return $this->getDecorated()->load($navigationId, $request, $context);
            }

            $key = $this->generateKey($navigationId, $request, $context);

            if ($key === null) {
                return $this->getDecorated()->load($navigationId, $request, $context);
            }

            $value = $this->cache->get($key, function (ItemInterface $item) use ($navigationId, $request, $context) {
                $name = self::buildName($navigationId);

                $response = $this->tracer->trace($name, fn () => $this->getDecorated()->load($navigationId, $request, $context));

                $item->tag($this->generateTags($navigationId, $response, $request, $context));

                return CacheValueCompressor::compress($response);
            });

            return CacheValueCompressor::uncompress($value);
        });
    }

    private function generateKey(string $navigationId, Request $request, SalesChannelContext $context): ?string
    {
        $parts = [...$request->query->all(), ...$request->request->all(), ...[$this->generator->getSalesChannelContextHash($context, [RuleAreas::CATEGORY_AREA, RuleAreas::PRODUCT_AREA])]];

        $event = new CategoryRouteCacheKeyEvent($navigationId, $parts, $request, $context, null);
        $this->dispatcher->dispatch($event);

        if (!$event->shouldCache()) {
            return null;
        }

        return self::buildName($navigationId) . '-' . md5(JsonFieldSerializer::encodeJson($event->getParts()));
    }

    /**
     * @return array<string>
     */
    private function generateTags(string $navigationId, CategoryRouteResponse $response, Request $request, SalesChannelContext $context): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($navigationId)),
            $this->extractProductIds($response),
            [self::buildName($navigationId)]
        );

        $event = new CategoryRouteCacheTagsEvent($navigationId, $tags, $request, $response, $context, null);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }

    /**
     * @return array<string>
     */
    private function extractProductIds(CategoryRouteResponse $response): array
    {
        $page = $response->getCategory()->getCmsPage();

        if ($page === null) {
            return [];
        }

        $ids = [];
        $streamIds = [];

        $slots = $page->getElementsOfType('product-slider');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $slider = $slot->getData();

            if (!$slider instanceof ProductSliderStruct) {
                continue;
            }

            if ($slider->getStreamId() !== null) {
                $streamIds[] = $slider->getStreamId();
            }

            if ($slider->getProducts() === null) {
                continue;
            }
            foreach ($slider->getProducts() as $product) {
                $ids[] = $product->getId();
                $ids[] = $product->getParentId();
            }
        }

        $slots = $page->getElementsOfType('product-box');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $box = $slot->getData();

            if (!$box instanceof ProductBoxStruct) {
                continue;
            }
            if ($box->getProduct() === null) {
                continue;
            }

            $ids[] = $box->getProduct()->getId();
            $ids[] = $box->getProduct()->getParentId();
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return [...array_map(EntityCacheKeyGenerator::buildProductTag(...), $ids), ...array_map(EntityCacheKeyGenerator::buildStreamTag(...), $streamIds), ...[EntityCacheKeyGenerator::buildCmsTag($page->getId())]];
    }
}