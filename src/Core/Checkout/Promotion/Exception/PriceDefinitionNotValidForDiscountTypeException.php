<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Promotion\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PriceDefinitionNotValidForDiscountTypeException extends LaserHttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__INVALID_PRICE_DEFINITION_FOR_DISCOUNT_TYPE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}