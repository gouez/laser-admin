<?php declare(strict_types=1);

namespace Laser\Core\Content\Media\Exception;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\LaserHttpException;

#[Package('content')]
class UploadException extends LaserHttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct('{{ message }}', ['message' => $message]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__MEDIA_UPLOAD';
    }
}
