<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('customer-order')]
class NoHashProvidedException extends LaserHttpException
{
    public function __construct()
    {
        parent::__construct(
            'The given hash is empty.'
        );
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__NO_HASH_PROVIDED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
