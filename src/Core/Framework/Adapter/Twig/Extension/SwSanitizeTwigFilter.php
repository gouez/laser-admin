<?php declare(strict_types=1);

namespace Laser\Core\Framework\Adapter\Twig\Extension;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Util\HtmlSanitizer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

#[Package('core')]
class SwSanitizeTwigFilter extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct(private readonly HtmlSanitizer $sanitizer)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('sw_sanitize', $this->sanitize(...), ['is_safe' => ['html']]),
        ];
    }

    public function sanitize(string $text, ?array $options = [], bool $override = false): string
    {
        return $this->sanitizer->sanitize($text, $options, $override);
    }
}
