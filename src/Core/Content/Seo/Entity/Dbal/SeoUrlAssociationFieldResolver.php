<?php declare(strict_types=1);

namespace Laser\Core\Content\Seo\Entity\Dbal;

use Laser\Core\Content\Seo\Entity\Field\SeoUrlAssociationField;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\AbstractFieldResolver;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\FieldResolverContext;
use Laser\Core\Framework\Feature;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Uuid\Uuid;

/**
 * @deprecated tag:v6.6.0 - will be removed
 */
#[Package('sales-channel')]
class SeoUrlAssociationFieldResolver extends AbstractFieldResolver
{
    public function join(FieldResolverContext $context): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.6.0.0',
            Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.6.0.0')
        );

        $field = $context->getField();
        if (!$field instanceof SeoUrlAssociationField) {
            return $context->getAlias();
        }

        $context->getQuery()->addState(EntityDefinitionQueryHelper::HAS_TO_MANY_JOIN);

        $alias = $context->getAlias() . '.' . $field->getPropertyName();
        if ($context->getQuery()->hasState($alias)) {
            return $alias;
        }

        $context->getQuery()->addState($alias);

        $routeParamKey = 'route_' . Uuid::randomHex();
        $parameters = [
            '#source#' => EntityDefinitionQueryHelper::escape($context->getAlias()) . '.' . EntityDefinitionQueryHelper::escape($field->getLocalField()),
            '#alias#' => EntityDefinitionQueryHelper::escape($alias),
            '#reference_column#' => EntityDefinitionQueryHelper::escape($field->getReferenceField()),
            '#root#' => EntityDefinitionQueryHelper::escape($context->getAlias()),
        ];

        $context->getQuery()->leftJoin(
            EntityDefinitionQueryHelper::escape($context->getAlias()),
            EntityDefinitionQueryHelper::escape($field->getReferenceDefinition()->getEntityName()),
            EntityDefinitionQueryHelper::escape($alias),
            str_replace(
                array_keys($parameters),
                array_values($parameters),
                '#source# = #alias#.#reference_column#
                AND #alias#.route_name = :' . $routeParamKey . '
                AND #alias#.is_deleted = 0'
            )
        );

        $context->getQuery()->setParameter($routeParamKey, $field->getRouteName());

        return $alias;
    }
}
