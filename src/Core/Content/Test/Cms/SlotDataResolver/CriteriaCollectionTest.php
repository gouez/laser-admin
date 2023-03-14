<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\Cms\SlotDataResolver;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Category\CategoryDefinition;
use Laser\Core\Content\Cms\DataResolver\CriteriaCollection;
use Laser\Core\Content\Cms\Exception\DuplicateCriteriaKeyException;
use Laser\Core\Content\Media\MediaDefinition;
use Laser\Core\Content\Product\ProductDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * @internal
 */
class CriteriaCollectionTest extends TestCase
{
    public function testAddSingleCriteria(): void
    {
        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());

        // test array return
        static::assertCount(1, $collection->all());

        // test iterator
        static::assertCount(1, $collection);
    }

    public function testAddMultipleCriteriaOfDifferentDefinition(): void
    {
        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());
        $collection->add('key2', MediaDefinition::class, new Criteria());
        $collection->add('key3', CategoryDefinition::class, new Criteria());

        // test array return
        static::assertCount(3, $collection->all());

        // test iterator
        static::assertCount(3, $collection);
    }

    public function testAddMultipleCriteriaOfSameDefinition(): void
    {
        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());
        $collection->add('key2', ProductDefinition::class, new Criteria());
        $collection->add('key3', ProductDefinition::class, new Criteria());

        // test array return
        static::assertCount(1, $collection->all());

        // test iterator
        static::assertCount(1, $collection);

        // test indexed by definition
        static::assertCount(3, $collection->all()[ProductDefinition::class]);
    }

    public function testAddDuplicates(): void
    {
        $this->expectException(DuplicateCriteriaKeyException::class);
        $this->expectExceptionMessage('The key "dup_key" is duplicated in the criteria collection.');

        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());
        $collection->add('dup_key', ProductDefinition::class, new Criteria());
        $collection->add('dup_key', ProductDefinition::class, new Criteria());
    }
}
