<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\ImportExport\Processing\Mapping;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\ImportExport\Processing\Mapping\CriteriaBuilder;
use Laser\Core\Content\ImportExport\Struct\Config;
use Laser\Core\Content\Product\ProductDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
#[Package('system-settings')]
class CriteriaBuilderTest extends TestCase
{
    use KernelTestBehaviour;

    public function testNoAssociations(): void
    {
        $criteriaBuild = new CriteriaBuilder($this->getContainer()->get(ProductDefinition::class));

        $criteria = new Criteria();
        $config = new Config(
            [
                'name',
            ],
            [],
            []
        );
        $criteriaBuild->enrichCriteria($config, $criteria);

        static::assertEmpty($criteria->getAssociations());
    }

    public function testAssociations(): void
    {
        $criteriaBuild = new CriteriaBuilder($this->getContainer()->get(ProductDefinition::class));

        $criteria = new Criteria();
        $config = new Config(
            [
                'name',
                'translations.name',
                'visibilities.search',
                'manufacturer.media.translations.title',
            ],
            [],
            []
        );
        $criteriaBuild->enrichCriteria($config, $criteria);

        $associations = $criteria->getAssociations();
        static::assertNotEmpty($associations);

        static::assertArrayHasKey('translations', $associations);
        static::assertInstanceOf(Criteria::class, $associations['translations']);

        static::assertArrayHasKey('visibilities', $associations);
        static::assertInstanceOf(Criteria::class, $associations['visibilities']);

        static::assertArrayHasKey('manufacturer', $associations);
        static::assertInstanceOf(Criteria::class, $associations['manufacturer']);

        $manufacturerAssociations = $associations['manufacturer']->getAssociations();
        static::assertArrayHasKey('media', $manufacturerAssociations);
        static::assertInstanceOf(Criteria::class, $manufacturerAssociations['media']);

        $manufacturerMediaAssociations = $manufacturerAssociations['media']->getAssociations();
        static::assertArrayHasKey('translations', $manufacturerMediaAssociations);
        static::assertInstanceOf(Criteria::class, $manufacturerMediaAssociations['translations']);
    }
}