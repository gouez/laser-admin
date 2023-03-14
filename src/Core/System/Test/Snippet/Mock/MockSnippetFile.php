<?php declare(strict_types=1);

namespace Laser\Core\System\Test\Snippet\Mock;

use Laser\Core\Framework\Log\Package;
use Laser\Core\System\Snippet\Files\AbstractSnippetFile;

/**
 * @internal
 */
#[Package('system-settings')]
class MockSnippetFile extends AbstractSnippetFile
{
    private readonly string $iso;

    public function __construct(
        private readonly string $name,
        ?string $iso = null,
        string $content = '{}',
        private readonly bool $isBase = true,
        private readonly string $technicalName = 'mock'
    ) {
        $this->iso = $iso ?? $name;
        file_put_contents($this->getPath(), $content);
    }

    public static function cleanup(): void
    {
        foreach (glob(__DIR__ . '/_fixtures/*.json') ?: [] as $mockFile) {
            unlink($mockFile);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return sprintf('%s/_fixtures/%s.json', __DIR__, $this->getName());
    }

    public function getIso(): string
    {
        return $this->iso;
    }

    public function getAuthor(): string
    {
        return $this->name;
    }

    public function isBase(): bool
    {
        return $this->isBase;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }
}
