<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Customer\SalesChannel;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Laser\Core\Defaults;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Test\TestDataCollection;
use Laser\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 *
 * @group store-api
 */
#[Package('customer-order')]
class ChangeLanguageRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use CustomerTestTrait;

    private KernelBrowser $browser;

    private TestDataCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new TestDataCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
    }

    public function testNotLoggedIn(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-language',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__CUSTOMER_NOT_LOGGED_IN', $response['errors'][0]['code']);
    }

    public function testValidLanguage(): void
    {
        $languageId = $this->getDeDeLanguageId();

        $this->getContainer()->get('sales_channel.repository')->update(
            [
                [
                    'id' => $this->ids->get('sales-channel'),
                    'languages' => [
                        [
                            'id' => $this->getDeDeLanguageId(),
                        ],
                    ],
                ],
            ],
            Context::createDefaultContext()
        );

        $id = $this->login($this->browser);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-language',
                [
                    'languageId' => $languageId,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('success', $response);

        /** @var Connection $connection */
        $connection = $this->getContainer()->get(Connection::class);
        $customer = $connection->fetchAllAssociative('SELECT * FROM customer WHERE id = :id', ['id' => Uuid::fromHexToBytes($id)]);

        static::assertEquals($languageId, Uuid::fromBytesToHex($customer[0]['language_id']));
    }

    public function testInvalidLanguage(): void
    {
        $languageId = $this->getDeDeLanguageId();

        $id = $this->login($this->browser);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-language',
                [
                    'languageId' => $languageId,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertEquals('The "language" entity with id "' . $languageId . '" does not exist.', $response['errors'][0]['detail']);

        /** @var Connection $connection */
        $connection = $this->getContainer()->get(Connection::class);
        $customer = $connection->fetchAllAssociative('SELECT * FROM customer WHERE id = :id', ['id' => Uuid::fromHexToBytes($id)]);

        static::assertEquals(Defaults::LANGUAGE_SYSTEM, Uuid::fromBytesToHex($customer[0]['language_id']));
    }
}
