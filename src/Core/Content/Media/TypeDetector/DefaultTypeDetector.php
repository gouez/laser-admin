<?php declare(strict_types=1);

namespace Laser\Core\Content\Media\TypeDetector;

use Laser\Core\Content\Media\File\MediaFile;
use Laser\Core\Content\Media\MediaType\AudioType;
use Laser\Core\Content\Media\MediaType\BinaryType;
use Laser\Core\Content\Media\MediaType\ImageType;
use Laser\Core\Content\Media\MediaType\MediaType;
use Laser\Core\Content\Media\MediaType\VideoType;
use Laser\Core\Framework\Log\Package;

#[Package('content')]
class DefaultTypeDetector implements TypeDetectorInterface
{
    public function detect(MediaFile $mediaFile, ?MediaType $previouslyDetectedType): ?MediaType
    {
        if ($previouslyDetectedType !== null) {
            return $previouslyDetectedType;
        }

        /** @var array<string>|false $mime */
        $mime = explode('/', $mediaFile->getMimeType());

        if ($mime === false) {
            return new BinaryType();
        }

        return match ($mime[0]) {
            'image' => new ImageType(),
            'video' => new VideoType(),
            'audio' => new AudioType(),
            default => new BinaryType(),
        };
    }
}
