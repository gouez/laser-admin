<?php declare(strict_types=1);

namespace Laser\Core\Framework\Api\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class IncompletePrimaryKeyException extends LaserHttpException
{
    public function __construct(array $primaryKeyFields)
    {
        parent::__construct(
            'The primary key consists of {{ fieldCount }} fields. Please provide values for the following fields: {{ fieldsString }}',
            ['fieldCount' => \count($primaryKeyFields), 'fields' => $primaryKeyFields, 'fieldsString' => implode(', ', $primaryKeyFields)]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INCOMPLETE_PRIMARY_KEY';
    }
}
