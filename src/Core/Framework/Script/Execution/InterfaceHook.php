<?php declare(strict_types=1);

namespace Laser\Core\Framework\Script\Execution;

use Laser\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
abstract class InterfaceHook extends Hook
{
    /**
     * Returns the hook for a specific function in this interface.
     */
    abstract public function getFunction(string $name): FunctionHook;

    /**
     * Services are defined in the function hooks
     */
    public static function getServiceIds(): array
    {
        return [];
    }
}
