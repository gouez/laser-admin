<?php declare(strict_types=1);

namespace Laser\Core\System\SalesChannel;

use Laser\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\Exception\LanguageOfSalesChannelDomainDeleteException;

#[Package('sales-channel')]
class SalesChannelExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*sales_channel_domain.*CONSTRAINT `fk.sales_channel_domain.language_id`/', $e->getMessage())) {
            return new LanguageOfSalesChannelDomainDeleteException($e);
        }

        return null;
    }
}
