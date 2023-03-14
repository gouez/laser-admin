<?php declare(strict_types=1);

namespace Laser\Core\Content\Sitemap\SalesChannel;

use Laser\Core\Content\Sitemap\Struct\SitemapCollection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\StoreApiResponse;

#[Package('sales-channel')]
class SitemapRouteResponse extends StoreApiResponse
{
    /**
     * @var SitemapCollection
     */
    protected $object;

    public function __construct(SitemapCollection $object)
    {
        parent::__construct($object);
    }

    public function getSitemaps(): SitemapCollection
    {
        return $this->object;
    }
}