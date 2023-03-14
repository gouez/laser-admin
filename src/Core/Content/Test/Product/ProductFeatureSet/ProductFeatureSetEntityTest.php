<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\Product\ProductFeatureSet;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetCollection;
use Laser\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetDefinition;
use Laser\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetEntity;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
#[Package('inventory')]
class ProductFeatureSetEntityTest extends TestCase
{
    use KernelTestBehaviour;

    public function testEntityDefinitionExists(): void
    {
        static::assertInstanceOf(
            ProductFeatureSetDefinition::class,
            new ProductFeatureSetDefinition()
        );
    }

    /**
     * @dataProvider definitionMethodProvider
     */
    public function testEntityDefinitionIsComplete(string $method, string $returnValue): void
    {
        $definition = $this->getContainer()->get(ProductFeatureSetDefinition::class);

        static::assertTrue(method_exists($definition, $method));
        static::assertEquals($returnValue, $definition->$method());
    }

    /**
     * @testWith    ["id"]
     *              ["name"]
     *              ["description"]
     *              ["features"]
     */
    public function testDefinitionFieldsAreComplete(string $field): void
    {
        $definition = $this->getContainer()->get(ProductFeatureSetDefinition::class);

        static::assertTrue($definition->getFields()->has($field));
    }

    public function testEntityExists(): void
    {
        static::assertInstanceOf(
            ProductFeatureSetEntity::class,
            new ProductFeatureSetEntity()
        );
    }

    /**
     * @testWith    ["getName"]
     *              ["getDescription"]
     *              ["getFeatures"]
     *              ["getTranslations"]
     */
    public function testEntityIsComplete(string $method): void
    {
        static::assertTrue(method_exists(ProductFeatureSetEntity::class, $method));
    }

    public function testCollectionExists(): void
    {
        static::assertInstanceOf(
            ProductFeatureSetCollection::class,
            new ProductFeatureSetCollection()
        );
    }

    public function testRepositoryIsWorking(): void
    {
        static::assertInstanceOf(EntityRepository::class, $this->getContainer()->get('product_feature_set.repository'));
    }

    public function testTranslationReferenceFieldIsCorrect(): void
    {
        $translationsField = $this->getContainer()->get(ProductFeatureSetDefinition::class)->getField('translations');

        static::assertInstanceOf(TranslationsAssociationField::class, $translationsField);
        static::assertEquals(
            sprintf('%s_id', ProductFeatureSetDefinition::ENTITY_NAME),
            $translationsField->getReferenceField()
        );
    }

    public static function definitionMethodProvider(): array
    {
        return [
            [
                'getEntityName',
                'product_feature_set',
            ],
            [
                'getCollectionClass',
                ProductFeatureSetCollection::class,
            ],
            [
                'getEntityClass',
                ProductFeatureSetEntity::class,
            ],
        ];
    }
}