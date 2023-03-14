<?php declare(strict_types=1);

namespace Laser\Core\Content\Test\Category\Event;

use PHPUnit\Framework\TestCase;
use Laser\Core\Content\Category\Event\NavigationLoadedEvent;
use Laser\Core\Content\Category\Service\NavigationLoader;
use Laser\Core\Content\Category\Service\NavigationLoaderInterface;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Test\TestCaseHelper\CallableClass;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Laser\Core\Test\TestDefaults;

/**
 * @internal
 */
class NavigationLoadedEventTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var NavigationLoaderInterface
     */
    protected $loader;

    protected function setUp(): void
    {
        $this->loader = $this->getContainer()->get(NavigationLoader::class);
        parent::setUp();
    }

    public function testEventDispatched(): void
    {
        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $this->addEventListener($dispatcher, NavigationLoadedEvent::class, $listener);

        $context = $this->getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $navigationId = $context->getSalesChannel()->getNavigationCategoryId();

        $this->loader->load($navigationId, $context, $navigationId);
    }
}
