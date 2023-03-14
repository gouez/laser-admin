<?php declare(strict_types=1);

namespace Laser\Core\Content\ProductExport\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('sales-channel')]
class ExportNotGeneratedException extends LaserHttpException
{
    public function __construct()
    {
        parent::__construct('Export file has not been generated yet. Please make sure that the scheduled task are working or run the command manually.');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_EXPORT_NOT_GENERATED';
    }
}