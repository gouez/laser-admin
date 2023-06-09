<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Script\Execution;

use Laser\Core\Framework\Script\Execution\DeprecatedHook;

/**
 * @internal
 */
class DeprecatedTestHook extends TestHook implements DeprecatedHook
{
    public static function getDeprecationNotice(): string
    {
        return 'Hook "DeprecatedTestHook" is obviously deprecated.';
    }
}
