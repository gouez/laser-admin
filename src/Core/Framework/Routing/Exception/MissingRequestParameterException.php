<?php declare(strict_types=1);

namespace Laser\Core\Framework\Routing\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class MissingRequestParameterException extends LaserHttpException
{
    public function __construct(
        private readonly string $name,
        private readonly string $path = ''
    ) {
        parent::__construct('Parameter "{{ parameterName }}" is missing.', ['parameterName' => $name]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__MISSING_REQUEST_PARAMETER';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
