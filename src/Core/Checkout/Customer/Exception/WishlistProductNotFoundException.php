<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('customer-order')]
class WishlistProductNotFoundException extends LaserHttpException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            'Wishlist product with id {{ productId }} not found',
            ['productId' => $productId]
        );
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__WISHLIST_PRODUCT_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}