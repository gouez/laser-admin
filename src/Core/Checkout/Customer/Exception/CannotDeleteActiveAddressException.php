<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('customer-order')]
class CannotDeleteActiveAddressException extends LaserHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(
            'Customer address with id "{{ addressId }}" is an active address and cannot be deleted.',
            ['addressId' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__CUSTOMER_ADDRESS_IS_ACTIVE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}