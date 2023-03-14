<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Customer\SalesChannel;

use PHPUnit\Framework\TestCase;
use Laser\Core\Defaults;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Laser\Core\Framework\Test\TestDataCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 *
 * @group store-api
 */
#[Package('customer-order')]
class CustomerGroupRegistrationSettingsRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

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

    public function testInvalidId(): void
    {
        $this->browser
            ->request(
                'GET',
                '/store-api/customer-group-registration/config/' . Defaults::LANGUAGE_SYSTEM
            );

        $response = json_decode($this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(404, $this->browser->getResponse()->getStatusCode());

        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__CUSTOMER_GROUP_REGISTRATION_NOT_FOUND', $response['errors'][0]['code']);
    }

    public function testWithValidConfig(): void
    {
        $customerGroupRepository = $this->getContainer()->get('customer_group.repository');
        $customerGroupRepository->create([
            [
                'id' => $this->ids->create('group'),
                'name' => 'foo',
                'registrationActive' => true,
                'registrationTitle' => 'test',
                'registrationSalesChannels' => [['id' => $this->getSalesChannelApiSalesChannelId()]],
            ],
        ], Context::createDefaultContext());

        $this->browser
            ->request(
                'GET',
                '/store-api/customer-group-registration/config/' . $this->ids->get('group')
            );

        $response = json_decode($this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        static::assertSame($this->ids->get('group'), $response['id']);
        static::assertSame('test', $response['registrationTitle']);
    }
}
