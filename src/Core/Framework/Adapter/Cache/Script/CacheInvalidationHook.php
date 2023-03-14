<?php declare(strict_types=1);

namespace Laser\Core\Framework\Adapter\Cache\Script;

use Laser\Core\Framework\Adapter\Cache\Script\Facade\CacheInvalidatorFacadeHookFactory;
use Laser\Core\Framework\Adapter\Cache\Script\Facade\WrittenEventScriptFacade;
use Laser\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Script\Execution\Hook;

/**
 * Triggered whenever an entity is written.
 *
 * @hook-use-case custom_endpoint
 *
 * @since 6.4.9.0
 */
#[Package('core')]
class CacheInvalidationHook extends Hook
{
    final public const HOOK_NAME = 'cache-invalidation';

    private readonly WrittenEventScriptFacade $event;

    public function __construct(EntityWrittenContainerEvent $event)
    {
        $this->event = new WrittenEventScriptFacade($event);
        parent::__construct($event->getContext());
    }

    public function getEvent(): WrittenEventScriptFacade
    {
        return $this->event;
    }

    public static function getServiceIds(): array
    {
        return [
            CacheInvalidatorFacadeHookFactory::class,
        ];
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }
}
