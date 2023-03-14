<?php declare(strict_types=1);

namespace Laser\Core\System\SalesChannel\Context;

use Laser\Core\Framework\Log\Package;
use Laser\Core\PlatformRequest;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[Package('core')]
class SalesChannelContextValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        if ($argument->getType() !== SalesChannelContext::class) {
            return;
        }

        yield $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
    }
}
