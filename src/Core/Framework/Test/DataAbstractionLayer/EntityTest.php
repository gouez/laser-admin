<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer {
    use PHPUnit\Framework\TestCase;
    use Laser\Core\Content\Product\Aggregate\ProductPrice\ProductPriceDefinition;
    use Laser\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
    use Laser\Core\Content\Product\ProductDefinition;
    use Laser\Core\Content\Product\ProductEntity;
    use Laser\Core\Framework\DataAbstractionLayer\Entity;
    use Laser\Core\Framework\DataAbstractionLayer\FieldVisibility;
    use Laser\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
    use Laser\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
    use Test\Foo\FooBar;
    use Test\Foo\FooBarEntity;
    use Test\Foo\FooEntity;
    use Test\Foo\ProductEntityRelationEntity;

    /**
     * @internal
     */
    class EntityTest extends TestCase
    {
        public static function apiAliasDefaultsDataProvider(): iterable
        {
            yield [
                FooEntity::class,
                'foo',
            ];

            yield [
                FooBarEntity::class,
                'foo_bar',
            ];

            yield [
                FooBar::class,
                'foo_bar',
            ];

            yield [
                ProductEntityRelationEntity::class,
                'product_entity_relation',
            ];

            yield [
                ProductEntity::class,
                ProductDefinition::ENTITY_NAME,
            ];

            yield [
                ProductPriceEntity::class,
                ProductPriceDefinition::ENTITY_NAME,
            ];

            yield [
                SalesChannelDomainEntity::class,
                SalesChannelDomainDefinition::ENTITY_NAME,
            ];
        }

        /**
         * @dataProvider apiAliasDefaultsDataProvider
         */
        public function testApiAlias(string $class, string $expected): void
        {
            /** @var Entity $entity */
            $entity = new $class();

            static::assertSame($expected, $entity->getApiAlias());
        }

        public function testCustomApiAliasHasPrecedence(): void
        {
            $entity = new FooBarEntity();
            $entity->internalSetEntityData('custom_entity_name', new FieldVisibility([]));

            static::assertSame('custom_entity_name', $entity->getApiAlias());
        }
    }
}

namespace Test\Foo {
    use Laser\Core\Framework\DataAbstractionLayer\Entity;

    class FooEntity extends Entity
    {
    }

    class FooBarEntity extends Entity
    {
    }

    class FooBar extends Entity
    {
    }

    class ProductEntityRelationEntity extends Entity
    {
    }
}
