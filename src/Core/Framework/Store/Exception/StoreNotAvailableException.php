<?php declare(strict_types=1);

namespace Laser\Core\Framework\Store\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;

#[Package('merchant-services')]
class StoreNotAvailableException extends LaserHttpException
{
    public function __construct()
    {
        parent::__construct('Store is not available');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__STORE_NOT_AVAILABLE';
    }
}
