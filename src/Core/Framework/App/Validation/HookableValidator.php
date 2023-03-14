<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Validation;

use Laser\Core\Framework\App\Manifest\Manifest;
use Laser\Core\Framework\App\Validation\Error\ErrorCollection;
use Laser\Core\Framework\App\Validation\Error\MissingPermissionError;
use Laser\Core\Framework\App\Validation\Error\NotHookableError;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Webhook\Hookable\HookableEventCollector;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
#[Package('core')]
class HookableValidator extends AbstractManifestValidator
{
    public function __construct(private readonly HookableEventCollector $hookableEventCollector)
    {
    }

    public function validate(Manifest $manifest, Context $context): ErrorCollection
    {
        $errors = new ErrorCollection();
        $webhooks = $manifest->getWebhooks();
        $webhooks = $webhooks ? $webhooks->getWebhooks() : [];

        if (!$webhooks) {
            return $errors;
        }

        $appPrivileges = $manifest->getPermissions();
        $appPrivileges = $appPrivileges ? $appPrivileges->asParsedPrivileges() : [];
        $hookableEventNamesWithPrivileges = $this->hookableEventCollector->getHookableEventNamesWithPrivileges($context);
        $hookableEventNames = array_keys($hookableEventNamesWithPrivileges);

        $notHookable = [];
        $missingPermissions = [];
        foreach ($webhooks as $webhook) {
            // validate supported webhooks
            if (!\in_array($webhook->getEvent(), $hookableEventNames, true)) {
                $notHookable[] = $webhook->getName() . ': ' . $webhook->getEvent();

                continue;
            }

            // validate permissions
            foreach ($hookableEventNamesWithPrivileges[$webhook->getEvent()]['privileges'] as $privilege) {
                if (\in_array($privilege, $appPrivileges, true)) {
                    continue;
                }

                $missingPermissions[] = $privilege;
            }
        }

        if (!empty($notHookable)) {
            $errors->add(new NotHookableError($notHookable));
        }

        if (!empty($missingPermissions)) {
            $errors->add(new MissingPermissionError($missingPermissions));
        }

        return $errors;
    }
}
