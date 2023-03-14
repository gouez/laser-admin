<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\Product\Events;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Product\Events\ProductListingResolvePreviewEvent;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('inventory')]
class ProductListingResolvePreviewEventTest extends TestCase
{
    public function testReplace(): void
    {
        $event = new ProductListingResolvePreviewEvent(
            $this->createMock(SalesChannelContext::class),
            new Criteria(),
            ['p1' => 'p1'],
            true
        );

        $event->replace('p1', 'p2');
        static::assertSame(['p1' => 'p2'], $event->getMapping());
    }

    public function testReplaceException(): void
    {
        $event = new ProductListingResolvePreviewEvent(
            $this->createMock(SalesChannelContext::class),
            new Criteria(),
            ['p1' => 'p1'],
            true
        );

        static::expectException(\RuntimeException::class);
        $event->replace('p3', 'p2');
    }
}