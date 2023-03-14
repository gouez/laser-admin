<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\Product\SalesChannel\Detail;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Laser\Core\Content\Product\Events\ProductDetailRouteCacheTagsEvent;
use Laser\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Laser\Core\Defaults;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Test\IdsCollection;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Test\TestCaseHelper\CallableClass;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Laser\Core\System\Tax\TaxEntity;
use Laser\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @group cache
 * @group store-api
 */
class CachedProductDetailRouteTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const ALL_TAG = 'test-tag';

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    /**
     * @dataProvider invalidationProvider
     */
    public function testInvalidation(\Closure $closure, int $calls, bool $isTestingWithVariant = false): void
    {
        $this->getContainer()->get('cache.object')->invalidateTags([self::ALL_TAG]);

        $this->getContainer()->get('event_dispatcher')
            ->addListener(ProductDetailRouteCacheTagsEvent::class, static function (ProductDetailRouteCacheTagsEvent $event): void {
                $event->addTags([self::ALL_TAG]);
            });

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::exactly($calls))->method('__invoke');

        $this->getContainer()
            ->get('event_dispatcher')
            ->addListener(ProductDetailRouteCacheTagsEvent::class, $listener);

        $productId = Uuid::randomHex();
        $propertyId = Uuid::randomHex();
        $cmsPageId = $this->createCmsPage('product_detail');

        $this->createProduct([
            'id' => $productId,
            'properties' => [
                ['id' => $propertyId, 'name' => 'red', 'group' => ['name' => 'color']],
            ],
            'cmsPageId' => $cmsPageId,
        ]);

        if ($isTestingWithVariant) {
            $variantId = Uuid::randomHex();
            $variantPropertyId = Uuid::randomHex();

            $this->createProduct([
                'id' => $variantId,
                'parentId' => $productId,
                'name' => 'test variant',
                'productNumber' => 'test variant',
                'options' => [
                    ['id' => $variantPropertyId, 'name' => 'red', 'group' => ['name' => 'color']],
                ],
            ]);

            $productId = $variantId;
            $propertyId = $variantPropertyId;
        }

        $route = $this->getContainer()->get(ProductDetailRoute::class);
        $route->load($productId, new Request(), $this->context, new Criteria());
        $route->load($productId, new Request(), $this->context, new Criteria());

        $closure($propertyId, $this->getContainer());

        $route->load($productId, new Request(), $this->context, new Criteria());
        $route->load($productId, new Request(), $this->context, new Criteria());
    }

    public static function invalidationProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Cache is invalidated if the updated property is used by the product' => [
            function (string $propertyId, ContainerInterface $container): void {
                $update = ['id' => $propertyId, 'name' => 'yellow'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache is invalidated if the deleted property is used by the product' => [
            function (string $propertyId, ContainerInterface $container): void {
                $delete = ['id' => $propertyId];
                $container->get('property_group_option.repository')->delete([$delete], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache is invalidated if the updated options is used by the product' => [
            function (string $propertyId, ContainerInterface $container): void {
                $update = ['id' => $propertyId, 'name' => 'yellow'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            2,
            true,
        ];

        yield 'Cache is not invalidated if the updated property is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property2'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );
                $update = ['id' => $ids->get('property2'), 'name' => 'XL'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache is not invalidated if the deleted property is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property3'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );

                $delete = ['id' => $ids->get('property3')];
                $container->get('property_group_option.repository')->delete([$delete], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache is not invalidated if the updated options is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property2'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );
                $update = ['id' => $ids->get('property2'), 'name' => 'XL'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            1,
            true,
        ];

        yield 'Cache is not invalidated if the deleted options is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property3'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );

                $delete = ['id' => $ids->get('property3')];
                $container->get('property_group_option.repository')->delete([$delete], Context::createDefaultContext());
            },
            1,
            true,
        ];
    }

    /**
     * @param array<mixed> $data
     */
    private function createProduct(array $data = []): void
    {
        $ids = new IdsCollection();

        $tax = $this->context->getTaxRules()->first();

        static::assertInstanceOf(TaxEntity::class, $tax);

        $product = array_merge(
            [
                'name' => 'test',
                'productNumber' => 'test',
                'stock' => 10,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
                'tax' => ['id' => $tax->getId(), 'name' => 'test', 'taxRate' => 15],
                'visibilities' => [[
                    'salesChannelId' => $this->context->getSalesChannelId(),
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ]],
            ],
            $data
        );

        $this->getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());
    }

    private function createCmsPage(string $type): string
    {
        $cmsPageId = Uuid::randomHex();

        $cmsPage = [
            'id' => $cmsPageId,
            'name' => 'test page',
            'type' => $type,
        ];

        $this->getContainer()->get('cms_page.repository')->create([$cmsPage], Context::createDefaultContext());

        return $cmsPageId;
    }
}