<?php

declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer\Dbal;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Test\Product\ProductBuilder;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Laser\Core\Framework\Test\IdsCollection;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @internal
 */
class RepositoryIteratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testIteratedSearch(): void
    {
        $context = Context::createDefaultContext();
        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $this->getContainer()->get('system_config.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', 'core'));
        $criteria->setLimit(1);

        $iterator = new RepositoryIterator($systemConfigRepository, $context, $criteria);

        $offset = 1;
        while (($result = $iterator->fetch()) !== null) {
            static::assertNotEmpty($result->first()->getId());
            static::assertEquals(
                [new ContainsFilter('configurationKey', 'core')],
                $criteria->getFilters()
            );
            static::assertCount(0, $criteria->getPostFilters());
            static::assertEquals($offset, $criteria->getOffset());
            ++$offset;
        }
    }

    public function testFetchIdsIsNotRunningInfinitely(): void
    {
        $context = Context::createDefaultContext();
        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $this->getContainer()->get('system_config.repository');

        $iterator = new RepositoryIterator($systemConfigRepository, $context, new Criteria());

        $iteration = 0;
        while ($iterator->fetchIds() !== null && $iteration < 100) {
            ++$iteration;
        }

        static::assertTrue($iteration < 100);
    }

    public function testFetchIdAutoIncrement(): void
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');

        $context = Context::createDefaultContext();

        $ids = new IdsCollection();

        $builder = new ProductBuilder($ids, 'product1');
        $builder->price(1);
        $productRepository->create([$builder->build()], $context);

        $builder = new ProductBuilder($ids, 'product2');
        $builder->price(2);
        $productRepository->create([$builder->build()], $context);

        $builder = new ProductBuilder($ids, 'product3');
        $builder->price(3);
        $productRepository->create([$builder->build()], $context);

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $iterator = new RepositoryIterator($productRepository, $context, $criteria);

        $totalFetchedIds = 0;
        while ($iterator->fetchIds()) {
            ++$totalFetchedIds;
        }
        static::assertEquals($totalFetchedIds, 3);
    }
}
