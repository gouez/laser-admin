<?php declare(strict_types=1);

namespace Laser\Core\System\CustomEntity\Xml\Field;

use Laser\Core\Framework\Log\Package;
use Laser\Core\System\CustomEntity\Xml\Field\Traits\RequiredTrait;
use Laser\Core\System\CustomEntity\Xml\Field\Traits\TranslatableTrait;

#[Package('core')]
class TextField extends Field
{
    use TranslatableTrait;
    use RequiredTrait;

    protected bool $allowHtml = false;

    protected string $type = 'text';

    /**
     * @internal
     */
    public static function fromXml(\DOMElement $element): Field
    {
        return new self(self::parse($element));
    }

    public function allowHtml(): bool
    {
        return $this->allowHtml;
    }
}
