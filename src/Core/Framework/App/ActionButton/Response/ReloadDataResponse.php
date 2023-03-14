<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\ActionButton\Response;

use Laser\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class ReloadDataResponse extends ActionButtonResponse
{
    final public const ACTION_TYPE = 'reload';

    public function __construct()
    {
        parent::__construct(self::ACTION_TYPE);
    }
}
