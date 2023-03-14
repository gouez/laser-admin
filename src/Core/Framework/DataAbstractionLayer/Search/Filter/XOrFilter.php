<?php declare(strict_types=1);

namespace Laser\Core\Framework\DataAbstractionLayer\Search\Filter;

use Laser\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class XOrFilter extends MultiFilter
{
    /**
     * @param  Filter[] $queries
     */
    public function __construct(array $queries = [])
    {
        parent::__construct(self::CONNECTION_XOR, $queries);
    }
}
