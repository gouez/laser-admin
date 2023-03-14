<?php declare(strict_types=1);

namespace Laser\Core\Content\ImportExport\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('system-settings')]
class RequiredByUserException extends LaserHttpException
{
    public function __construct(string $column)
    {
        parent::__construct('{{ column }} is set to required by the user but has no value', [
            'column' => $column,
        ]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_REQUIRED_BY_USER';
    }
}