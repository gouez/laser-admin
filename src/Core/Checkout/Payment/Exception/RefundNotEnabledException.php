<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Payment\Exception;

use Laser\Core\Framework\Log\Package;

#[Package('checkout')]
class RefundNotEnabledException extends RefundProcessException
{
    public function __construct(?\Throwable $e = null)
    {
        parent::__construct(
            '',
            'The payment method does not allow refund handling.',
            [],
            $e
        );
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__REFUND_NOT_ENABLED_ERROR';
    }
}