<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\ScheduledTask;

use Laser\Core\Framework\App\Lifecycle\Update\AbstractAppUpdater;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: UpdateAppsTask::class)]
#[Package('core')]
final class UpdateAppsHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly AbstractAppUpdater $appUpdater
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        $this->appUpdater->updateApps(Context::createDefaultContext());
    }
}
