<?php declare(strict_types=1);

namespace Laser\Core\Content\Seo\Entity\Field;

use Laser\Core\Content\Seo\Entity\Dbal\SeoUrlAssociationFieldResolver;
use Laser\Core\Content\Seo\Entity\Serializer\SeoUrlFieldSerializer;
use Laser\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Extension;
use Laser\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Laser\Core\Framework\Feature;
use Laser\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.6.0 - will be removed
 */
#[Package('sales-channel')]
class SeoUrlAssociationField extends OneToManyAssociationField
{
    public function __construct(
        string $propertyName,
        private readonly string $routeName,
        string $localField = 'id'
    ) {
        parent::__construct($propertyName, SeoUrlDefinition::class, 'foreign_key', $localField);
        $this->addFlags(new Extension());
    }

    public function getRouteName(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.6.0.0',
            Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.6.0.0')
        );

        return $this->routeName;
    }

    protected function getSerializerClass(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.6.0.0',
            Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.6.0.0')
        );

        return SeoUrlFieldSerializer::class;
    }

    protected function getResolverClass(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.6.0.0',
            Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.6.0.0')
        );

        return SeoUrlAssociationFieldResolver::class;
    }
}
