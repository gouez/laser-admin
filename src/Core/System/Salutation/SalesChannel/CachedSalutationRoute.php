<?php declare(strict_types=1);

namespace Laser\Core\System\Salutation\SalesChannel;

use Laser\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Laser\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Laser\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Laser\Core\System\SalesChannel\StoreApiResponse;
use Laser\Core\System\Salutation\Event\SalutationRouteCacheKeyEvent;
use Laser\Core\System\Salutation\Event\SalutationRouteCacheTagsEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('customer-order')]
class CachedSalutationRoute extends AbstractSalutationRoute
{
    final public const ALL_TAG = 'salutation-route';

    /**
     * @internal
     *
     * @param AbstractCacheTracer<SalutationRouteResponse> $tracer
     * @param array<string> $states
     */
    public function __construct(
        private readonly AbstractSalutationRoute $decorated,
        private readonly CacheInterface $cache,
        private readonly EntityCacheKeyGenerator $generator,
        private readonly AbstractCacheTracer $tracer,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly array $states
    ) {
    }

    public static function buildName(): string
    {
        return 'salutation-route';
    }

    public function getDecorated(): AbstractSalutationRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/salutation', name: 'store-api.salutation', methods: ['GET', 'POST'], defaults: ['_entity' => 'salutation'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): SalutationRouteResponse
    {
        if ($context->hasState(...$this->states)) {
            return $this->getDecorated()->load($request, $context, $criteria);
        }

        $key = $this->generateKey($request, $context, $criteria);

        if ($key === null) {
            return $this->getDecorated()->load($request, $context, $criteria);
        }

        $value = $this->cache->get($key, function (ItemInterface $item) use ($request, $context, $criteria) {
            $name = self::buildName();

            $response = $this->tracer->trace($name, fn () => $this->getDecorated()->load($request, $context, $criteria));

            $item->tag($this->generateTags($request, $response, $context, $criteria));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(Request $request, SalesChannelContext $context, Criteria $criteria): ?string
    {
        $parts = [
            $this->generator->getCriteriaHash($criteria),
            $this->generator->getSalesChannelContextHash($context),
        ];

        $event = new SalutationRouteCacheKeyEvent($parts, $request, $context, $criteria);
        $this->dispatcher->dispatch($event);

        if (!$event->shouldCache()) {
            return null;
        }

        return self::buildName() . '-' . md5((string) JsonFieldSerializer::encodeJson($event->getParts()));
    }

    /**
     * @return array<string>
     */
    private function generateTags(Request $request, StoreApiResponse $response, SalesChannelContext $context, Criteria $criteria): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName()),
            [self::ALL_TAG]
        );

        $event = new SalutationRouteCacheTagsEvent($tags, $request, $response, $context, $criteria);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }
}