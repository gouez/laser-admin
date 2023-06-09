<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Aggregate\AppPaymentMethod;

use Laser\Core\Framework\DataAbstractionLayer\EntityCollection;
use Laser\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 *
 * @extends EntityCollection<AppPaymentMethodEntity>
 */
#[Package('core')]
class AppPaymentMethodCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AppPaymentMethodEntity::class;
    }
}
