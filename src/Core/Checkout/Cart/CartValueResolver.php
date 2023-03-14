<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart;

use Laser\Core\Checkout\Cart\SalesChannel\CartService;
use Laser\Core\Framework\Log\Package;
use Laser\Core\PlatformRequest;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[Package('checkout')]
class CartValueResolver implements ValueResolverInterface
{
    /**
     * @internal
     */
    public function __construct(private CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        if ($argument->getType() !== Cart::class) {
            return;
        }

        /** @var SalesChannelContext $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        yield $this->cartService->getCart($context->getToken(), $context);
    }
}