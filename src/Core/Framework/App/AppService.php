<?php declare(strict_types=1);

namespace Laser\Core\Framework\App;

use Laser\Core\Framework\App\Lifecycle\AbstractAppLifecycle;
use Laser\Core\Framework\App\Lifecycle\AppLifecycleIterator;
use Laser\Core\Framework\App\Lifecycle\RefreshableAppDryRun;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
#[Package('core')]
class AppService
{
    public function __construct(
        private readonly AppLifecycleIterator $appLifecycleIterator,
        private readonly AbstractAppLifecycle $appLifecycle
    ) {
    }

    /**
     * @param array<string> $installAppNames - Apps that should be installed
     */
    public function doRefreshApps(bool $activateInstalled, Context $context, array $installAppNames = []): array
    {
        return $this->appLifecycleIterator->iterateOverApps($this->appLifecycle, $activateInstalled, $context, $installAppNames);
    }

    public function getRefreshableAppInfo(Context $context): RefreshableAppDryRun
    {
        $appInfo = new RefreshableAppDryRun();

        $this->appLifecycleIterator->iterateOverApps($appInfo, false, $context);

        return $appInfo;
    }
}
