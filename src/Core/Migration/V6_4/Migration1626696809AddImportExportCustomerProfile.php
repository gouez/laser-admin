<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Content\ImportExport\ImportExportProfileTranslationDefinition;
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
class Migration1626696809AddImportExportCustomerProfile extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1626696809;
    }

    public function update(Connection $connection): void
    {
        $id = Uuid::randomBytes();

        $connection->insert('import_export_profile', [
            'id' => $id,
            'name' => 'Default customer',
            'system_default' => 1,
            'source_entity' => 'customer',
            'file_type' => 'text/csv',
            'delimiter' => ';',
            'enclosure' => '"',
            'mapping' => json_encode([
                ['key' => 'id', 'mappedKey' => 'id'],
                ['key' => 'salutation.salutationKey', 'mappedKey' => 'salutation'],
                ['key' => 'customerNumber', 'mappedKey' => 'customer_number'],
                ['key' => 'firstName', 'mappedKey' => 'first_name'],
                ['key' => 'lastName', 'mappedKey' => 'last_name'],
                ['key' => 'email', 'mappedKey' => 'email'],
                ['key' => 'active', 'mappedKey' => 'active'],
                ['key' => 'guest', 'mappedKey' => 'guest'],
                ['key' => 'group.translations.DEFAULT.name', 'mappedKey' => 'customer_group'],
                ['key' => 'language.locale.code', 'mappedKey' => 'language'],
                ['key' => 'salesChannel.translations.DEFAULT.name', 'mappedKey' => 'sales_channel'],
                ['key' => 'defaultPaymentMethod.translations.DEFAULT.name', 'mappedKey' => 'payment_method'],
                ['key' => 'defaultBillingAddress.id', 'mappedKey' => 'billing_id'],
                ['key' => 'defaultBillingAddress.salutation.salutationKey', 'mappedKey' => 'billing_salutation'],
                ['key' => 'defaultBillingAddress.title', 'mappedKey' => 'billing_title'],
                ['key' => 'defaultBillingAddress.firstName', 'mappedKey' => 'billing_first_name'],
                ['key' => 'defaultBillingAddress.lastName', 'mappedKey' => 'billing_last_name'],
                ['key' => 'defaultBillingAddress.company', 'mappedKey' => 'billing_company'],
                ['key' => 'defaultBillingAddress.street', 'mappedKey' => 'billing_street'],
                ['key' => 'defaultBillingAddress.zipcode', 'mappedKey' => 'billing_zipcode'],
                ['key' => 'defaultBillingAddress.city', 'mappedKey' => 'billing_city'],
                ['key' => 'defaultBillingAddress.country.iso', 'mappedKey' => 'billing_country'],
                ['key' => 'defaultBillingAddress.phoneNumber', 'mappedKey' => 'billing_phone_number'],
                ['key' => 'defaultShippingAddress.id', 'mappedKey' => 'shipping_id'],
                ['key' => 'defaultShippingAddress.salutation.salutationKey', 'mappedKey' => 'shipping_salutation'],
                ['key' => 'defaultShippingAddress.title', 'mappedKey' => 'shipping_title'],
                ['key' => 'defaultShippingAddress.firstName', 'mappedKey' => 'shipping_first_name'],
                ['key' => 'defaultShippingAddress.lastName', 'mappedKey' => 'shipping_last_name'],
                ['key' => 'defaultShippingAddress.company', 'mappedKey' => 'shipping_company'],
                ['key' => 'defaultShippingAddress.street', 'mappedKey' => 'shipping_street'],
                ['key' => 'defaultShippingAddress.zipcode', 'mappedKey' => 'shipping_zipcode'],
                ['key' => 'defaultShippingAddress.city', 'mappedKey' => 'shipping_city'],
                ['key' => 'defaultShippingAddress.country.iso', 'mappedKey' => 'shipping_country'],
                ['key' => 'defaultShippingAddress.phoneNumber', 'mappedKey' => 'shipping_phone_number'],
            ]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $translations = new Translations(
            [
                'import_export_profile_id' => $id,
                'label' => 'Standardprofil Kunde',
            ],
            [
                'import_export_profile_id' => $id,
                'label' => 'Default customer',
            ]
        );

        $this->importTranslation(ImportExportProfileTranslationDefinition::ENTITY_NAME, $translations, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
