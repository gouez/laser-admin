<?php declare(strict_types=1);

namespace Laser\Core\System\CustomEntity\Xml\Config\AdminUi\XmlElements;

use Laser\Core\Framework\Log\Package;
use Laser\Core\System\CustomEntity\Xml\Config\ConfigXmlElement;

/**
 * Represents the XML listing element
 *
 * admin-ui > entity > listing
 *
 * @internal
 */
#[Package('content')]
final class Listing extends ConfigXmlElement
{
    private function __construct(
        protected readonly Columns $columns
    ) {
    }

    public static function fromXml(\DOMElement $element): self
    {
        return new self(
            Columns::fromXml($element)
        );
    }

    public function getColumns(): Columns
    {
        return $this->columns;
    }
}
