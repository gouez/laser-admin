<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Laser\Core\Content\Product\SalesChannel\Sorting\ProductSortingDefinition;
use Laser\Core\Defaults;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\Migration\Traits\ImportTranslationsTrait;
use Laser\Core\Migration\Traits\Translations;

/**
 * @internal
 */
#[Package('core')]
class Migration1600338271AddTopsellerSorting extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1600338271;
    }

    public function update(Connection $connection): void
    {
        $this->createDefaultSortingsWithTranslations($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    public function createDefaultSortingsWithTranslations(Connection $connection): void
    {
        $sorting = $this->getTopsellerSorting();

        $translations = $sorting['translations'];

        unset($sorting['translations']);

        $connection->insert(ProductSortingDefinition::ENTITY_NAME, $sorting);

        $translations = new Translations(
            ['product_sorting_id' => $sorting['id'], 'label' => $translations['de-DE']],
            ['product_sorting_id' => $sorting['id'], 'label' => $translations['en-GB']]
        );

        $this->importTranslation('product_sorting_translation', $translations, $connection);
    }

    /**
     * @return array{id: string, priority: int, active: int, locked: int, fields: string, created_at: string, translations: array{de-DE: string, en-GB: string}}
     */
    private function getTopsellerSorting(): array
    {
        return [
            'id' => Uuid::randomBytes(),
            'url_key' => 'topseller',
            'priority' => 0,
            'active' => 1,
            'locked' => 0,
            'fields' => json_encode([['field' => 'product.sales', 'order' => 'desc', 'priority' => 1, 'naturalSorting' => 0]], \JSON_THROW_ON_ERROR),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'translations' => [
                'de-DE' => 'Topseller',
                'en-GB' => 'Topseller',
            ],
        ];
    }
}
