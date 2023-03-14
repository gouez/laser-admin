<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Cms\Xml;

use Laser\Core\Framework\App\Manifest\Xml\XmlElement;
use Laser\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('content')]
class Block extends XmlElement
{
    final public const TRANSLATABLE_FIELDS = [
        'label',
    ];

    protected string $name;

    protected string $category;

    protected array $label = [];

    /**
     * @var Slot[]
     */
    protected array $slots = [];

    protected DefaultConfig $defaultConfig;

    private function __construct(array $data)
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public static function fromXml(\DOMElement $element): self
    {
        return new self(self::parseBlocks($element));
    }

    public function toArray(string $defaultLocale): array
    {
        $data = parent::toArray($defaultLocale);

        foreach (self::TRANSLATABLE_FIELDS as $TRANSLATABLE_FIELD) {
            $translatableField = self::kebabCaseToCamelCase($TRANSLATABLE_FIELD);

            $data[$translatableField] = $this->ensureTranslationForDefaultLanguageExist(
                $data[$translatableField],
                $defaultLocale
            );
        }

        return $data;
    }

    public function toEntityArray(string $appId, string $defaultLocale): array
    {
        $slots = [];

        foreach ($this->slots as $slot) {
            $slots[$slot->getName()] = [
                'type' => $slot->getType(),
                'default' => [
                    'config' => $slot->getConfig()->toArray($defaultLocale),
                ],
            ];
        }

        return [
            'appId' => $appId,
            'name' => $this->getName(),
            'label' => $this->ensureTranslationForDefaultLanguageExist($this->getLabel(), $defaultLocale),
            'block' => [
                'name' => $this->getName(),
                'category' => $this->getCategory(),
                'label' => $this->ensureTranslationForDefaultLanguageExist($this->getLabel(), $defaultLocale),
                'slots' => $slots,
                'defaultConfig' => $this->defaultConfig->toArray($defaultLocale),
            ],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getLabel(): array
    {
        return $this->label;
    }

    public function getSlots(): array
    {
        return $this->slots;
    }

    public function getDefaultConfig(): DefaultConfig
    {
        return $this->defaultConfig;
    }

    private static function parseBlocks(\DOMElement $element): array
    {
        $values = [];

        foreach ($element->childNodes as $block) {
            if (!$block instanceof \DOMElement) {
                continue;
            }

            $values = self::parseChild($block, $values);
        }

        return $values;
    }

    private static function parseChild(\DOMElement $child, array $values): array
    {
        // translated
        if (\in_array($child->tagName, self::TRANSLATABLE_FIELDS, true)) {
            return self::mapTranslatedTag($child, $values);
        }

        if ($child->tagName === 'slots') {
            $values[$child->tagName] = self::parseChildNodes(
                $child,
                static fn (\DOMElement $element): Slot => Slot::fromXml($element)
            );

            return $values;
        }

        if ($child->tagName === 'default-config') {
            $values[self::kebabCaseToCamelCase($child->tagName)] = DefaultConfig::fromXml($child);

            return $values;
        }

        $values[self::kebabCaseToCamelCase($child->tagName)] = $child->nodeValue;

        return $values;
    }
}
