<?php declare(strict_types=1);

namespace Laser\Core\Framework\Adapter\Cache;

use Laser\Core\Framework\Log\Package;

/**
 * @template TCachedContent
 */
#[Package('core')]
abstract class AbstractCacheTracer
{
    /**
     * @return AbstractCacheTracer<TCachedContent>
     */
    abstract public function getDecorated(): AbstractCacheTracer;

    /**
     * @return TCachedContent
     */
    abstract public function trace(string $key, \Closure $param);

    abstract public function get(string $key): array;
}
