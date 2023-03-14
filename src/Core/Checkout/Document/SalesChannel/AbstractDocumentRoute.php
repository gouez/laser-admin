<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Document\SalesChannel;

use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This route is used to get the generated document from a documentId
 */
#[Package('customer-order')]
abstract class AbstractDocumentRoute
{
    abstract public function getDecorated(): AbstractDocumentRoute;

    abstract public function download(string $documentId, Request $request, SalesChannelContext $context, string $deepLinkCode = ''): Response;
}
