<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Order\SalesChannel;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\SalesChannel\CartService;
use Laser\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Laser\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Laser\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Laser\Core\Checkout\Customer\CustomerEntity;
use Laser\Core\Checkout\Order\OrderEntity;
use Laser\Core\Checkout\Order\SalesChannel\OrderService;
use Laser\Core\Content\MailTemplate\Service\Event\MailSentEvent;
use Laser\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Laser\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Laser\Core\Defaults;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Test\TestCaseBase\CountryAddToSalesChannelTestBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\Framework\Validation\DataBag\RequestDataBag;
use Laser\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextService;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Laser\Core\Test\TestDefaults;
use Laser\Storefront\Controller\AccountOrderController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @internal
 *
 * @group slow
 */
#[Package('customer-order')]
class OrderServiceTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;
    use CountryAddToSalesChannelTestBehaviour;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var EntityRepository
     */
    private $orderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = $this->getContainer()->get(OrderService::class);

        $this->orderRepository = $this->getContainer()->get('order.repository');

        $this->cleanDefaultSalesChannelDomain();
        $this->addCountriesToSalesChannel();

        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create(
            '',
            TestDefaults::SALES_CHANNEL,
            [SalesChannelContextService::CUSTOMER_ID => $this->createCustomer('Jon', 'Doe')]
        );
    }

    public function testOrderDeliveryStateTransition(): void
    {
        $orderId = $this->performOrder();

        // getting the id of the order delivery
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('deliveries');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        $deliveries = $order->getDeliveries();
        static::assertNotNull($deliveries);
        $delivery = $deliveries->first();
        static::assertNotNull($delivery);
        $orderDeliveryId = $delivery->getId();

        $this->orderService->orderDeliveryStateTransition(
            $orderDeliveryId,
            'ship',
            new RequestDataBag(),
            $this->salesChannelContext->getContext()
        );

        /** @var OrderEntity $updatedOrder */
        $updatedOrder = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        $deliveries = $updatedOrder->getDeliveries();
        static::assertNotNull($deliveries);
        $delivery = $deliveries->first();
        static::assertNotNull($delivery);
        $updatedDeliveryState = $delivery->getStateMachineState()->getTechnicalName();

        static::assertSame('shipped', $updatedDeliveryState);
    }

    public function testOrderDeliveryStateTransitionSendsMail(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $orderId = $this->performOrder();

        // getting the id of the order delivery
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('deliveries');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        $deliveries = $order->getDeliveries();
        static::assertNotNull($deliveries);
        $delivery = $deliveries->first();
        static::assertNotNull($delivery);
        $orderDeliveryId = $delivery->getId();

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $url = $domain . '/account/order/' . $order->getDeepLinkCode();
        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $url): void {
            $phpunit->assertStringContainsString('The new status is as follows: Cancelled.', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString($url, $event->getContents()['text/html']);
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->orderDeliveryStateTransition(
            $orderDeliveryId,
            'cancel',
            new RequestDataBag(),
            $this->salesChannelContext->getContext()
        );

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    public function testSkipOrderDeliveryStateTransitionSendsMail(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $orderId = $this->performOrder();

        // getting the id of the order delivery
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('deliveries');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        static::assertNotNull($deliveries = $order->getDeliveries());
        static::assertNotNull($delivery = $deliveries->first());
        $orderDeliveryId = $delivery->getId();

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $url = $domain . '/account/order/' . $order->getDeepLinkCode();
        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $url): void {
            $phpunit->assertStringContainsString('The new status is as follows: Cancelled.', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString($url, $event->getContents()['text/html']);
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->salesChannelContext
            ->getContext()
            ->addExtension(MailSendSubscriberConfig::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(true, [], []));

        $this->orderService->orderDeliveryStateTransition(
            $orderDeliveryId,
            'cancel',
            new RequestDataBag(['sendMail' => false]),
            $this->salesChannelContext->getContext()
        );

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertFalse($eventDidRun, 'The mail.sent Event did run');
    }

    public function testOrderDeliveryStateTransitionSendsMailDe(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $previousContext = $this->salesChannelContext;
        $this->salesChannelContext = $contextFactory->create(
            '',
            TestDefaults::SALES_CHANNEL,
            [
                SalesChannelContextService::CUSTOMER_ID => $this->createCustomer('Jon', 'De'),
                SalesChannelContextService::LANGUAGE_ID => $this->getDeDeLanguageId(),
            ]
        );
        $orderId = $this->performOrder();

        // getting the id of the order delivery
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('deliveries');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        static::assertNotNull($deliveries = $order->getDeliveries());
        static::assertNotNull($delivery = $deliveries->first());
        $orderDeliveryId = $delivery->getId();

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, $this->getDeDeLanguageId());

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $url = $domain . '/account/order/' . $order->getDeepLinkCode();
        $eventDidRun = false;
        $innerEvent = null;

        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, &$innerEvent): void {
            $innerEvent = $event;
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->orderDeliveryStateTransition(
            $orderDeliveryId,
            'cancel',
            new RequestDataBag(),
            Context::createDefaultContext() //DefaultContext is intended to test if the language of the order is used
        );

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertNotNull($innerEvent);
        static::assertStringContainsString('Die Bestellung hat jetzt den Lieferstatus: Abgebrochen.', $innerEvent->getContents()['text/html']);
        static::assertStringContainsString($url, $innerEvent->getContents()['text/html']);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
        $this->salesChannelContext = $previousContext;
    }

    public function testOrderTransactionStateTransition(): void
    {
        $orderId = $this->performOrder();

        // getting the id of the order transaction
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        static::assertNotNull($transactions = $order->getTransactions());
        static::assertNotNull($transaction = $transactions->first());
        $orderTransactionId = $transaction->getId();

        $this->orderService->orderTransactionStateTransition(
            $orderTransactionId,
            'remind',
            new RequestDataBag(),
            $this->salesChannelContext->getContext()
        );

        /** @var OrderEntity $updatedOrder */
        $updatedOrder = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        static::assertNotNull($transactions = $updatedOrder->getTransactions());
        static::assertNotNull($transaction = $transactions->first());
        $updatedTransactionState = $transaction->getStateMachineState()->getTechnicalName();

        static::assertSame('reminded', $updatedTransactionState);
    }

    public function testOrderTransactionStateTransitionSendsMail(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $orderId = $this->performOrder();

        // getting the id of the order transaction
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        static::assertNotNull($transactions = $order->getTransactions());
        static::assertNotNull($transaction = $transactions->first());
        $orderTransactionId = $transaction->getId();

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $url = $domain . '/account/order/' . $order->getDeepLinkCode();
        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $url): void {
            $phpunit->assertStringContainsString('The new status is as follows: Paid (partially).', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString($url, $event->getContents()['text/html']);
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->orderTransactionStateTransition(
            $orderTransactionId,
            'pay_partially',
            new RequestDataBag(),
            $this->salesChannelContext->getContext()
        );

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    public function testSkipOrderTransactionStateTransitionSendsMail(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $orderId = $this->performOrder();

        // getting the id of the order transaction
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        static::assertNotNull($transactions = $order->getTransactions());
        static::assertNotNull($transaction = $transactions->first());
        $orderTransactionId = $transaction->getId();

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $url = $domain . '/account/order/' . $order->getDeepLinkCode();
        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $url): void {
            $phpunit->assertStringContainsString('The new status is as follows: Paid (partially).', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString($url, $event->getContents()['text/html']);
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->salesChannelContext
            ->getContext()
            ->addExtension(MailSendSubscriberConfig::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(true, [], []));

        $this->orderService->orderTransactionStateTransition(
            $orderTransactionId,
            'pay_partially',
            new RequestDataBag(['sendMail' => false]),
            $this->salesChannelContext->getContext()
        );

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertFalse($eventDidRun, 'The mail.sent Event did not run');
    }

    public function testCreateOrder(): void
    {
        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        $orderId = $this->orderService->createOrder($data, $this->salesChannelContext);

        $criteria = new Criteria([$orderId]);

        /** @var OrderEntity $newlyCreatedOrder */
        $newlyCreatedOrder = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();

        static::assertInstanceOf(OrderEntity::class, $newlyCreatedOrder);
        static::assertSame($orderId, $newlyCreatedOrder->getId());
    }

    public function testCreateOrderSavesVatIdsInOrderCustomer(): void
    {
        $vatIds = ['DE123456789'];
        $additionalData = [
            'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
            'billingAddress' => [
                'company' => 'Test Company',
                'department' => 'Test Department',
            ],
            'vatIds' => $vatIds,
        ];
        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create(
            '',
            TestDefaults::SALES_CHANNEL,
            [SalesChannelContextService::CUSTOMER_ID => $this->createCustomer('Jon', 'Doe', $additionalData)]
        );

        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        $orderId = $this->orderService->createOrder($data, $this->salesChannelContext);

        $criteria = new Criteria([$orderId]);

        /** @var OrderEntity $newlyCreatedOrder */
        $newlyCreatedOrder = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();

        static::assertInstanceOf(OrderEntity::class, $newlyCreatedOrder);
        static::assertSame($orderId, $newlyCreatedOrder->getId());
        $orderCustomer = $newlyCreatedOrder->getOrderCustomer();
        static::assertNotNull($orderCustomer);
        static::assertSame($vatIds, $orderCustomer->getVatIds());
    }

    public function testCreateOrderSendsMail(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $eventDidRun = false;
        $listenerClosure = function () use (&$eventDidRun): void {
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->createOrder($data, $this->salesChannelContext);

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    public function testOrderStateTransition(): void
    {
        $orderId = $this->performOrder();

        $this->orderService->orderStateTransition($orderId, 'cancel', new ParameterBag(), $this->salesChannelContext->getContext());

        $criteria = new Criteria([$orderId]);
        /** @var OrderEntity $cancelledOrder */
        $cancelledOrder = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();
        $state = $cancelledOrder->getStateMachineState();

        static::assertNotNull($state);
        static::assertSame('cancelled', $state->getTechnicalName());
    }

    public function testOrderStateTransitionSendsMail(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $orderId = $this->performOrder();

        $domain = 'http://laser.' . Uuid::randomHex();
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $criteria = new Criteria([$orderId]);

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $this->salesChannelContext->getContext())->first();

        $url = $domain . '/account/order/' . $order->getDeepLinkCode();
        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $url): void {
            $phpunit->assertStringContainsString('The new status is as follows: Cancelled.', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString($url, $event->getContents()['text/html']);
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->orderStateTransition($orderId, 'cancel', new ParameterBag(), $this->salesChannelContext->getContext());

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    public function testMailTemplateHasCorrectDomain(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        $firstDomain = 'http://laser.first-domain';
        $this->setDomainForSalesChannel($firstDomain, Defaults::LANGUAGE_SYSTEM);

        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');

        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('id', Defaults::LANGUAGE_SYSTEM),
                ]
            )
        );

        $languageId = $languageRepository->searchIds($criteria, $this->salesChannelContext->getContext())->firstId();
        static::assertNotNull($languageId);
        $secondDomain = 'http://laser.second-domain';
        $this->setDomainForSalesChannel($secondDomain, $languageId);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $firstDomain, $secondDomain): void {
            $phpunit->assertStringContainsString($firstDomain, $event->getContents()['text/html']);
            $phpunit->assertThat($event->getContents()['text/html'], $this->logicalNot($this->stringContains($secondDomain)));
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->createOrder($data, $this->salesChannelContext);

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    public function testMailTemplateHandlesVirtualDomains(): void
    {
        if (!$this->getContainer()->has(AccountOrderController::class)) {
            // ToDo: NEXT-16882 - Reactivate tests again
            static::markTestSkipped('Order mail tests should be fixed without storefront in NEXT-16882');
        }

        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        $domain = 'http://laser.test/virtual-domain';
        $this->setDomainForSalesChannel($domain, Defaults::LANGUAGE_SYSTEM);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $url = $domain . '/account/order';
        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit, $url): void {
            $phpunit->assertStringContainsString($url, $event->getContents()['text/html']);
            $eventDidRun = true;
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->orderService->createOrder($data, $this->salesChannelContext);

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    private function performOrder(): string
    {
        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        return $this->orderService->createOrder($data, $this->salesChannelContext);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createCustomer(string $firstName, string $lastName, array $options = []): string
    {
        $customerId = Uuid::randomHex();
        $salutationId = $this->getValidSalutationId();
        $paymentMethodId = $this->getValidPaymentMethodId();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $customerId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'city' => 'Schöppingen',
                'street' => 'Ebbinghoff 10',
                'zipcode' => '48624',
                'salutationId' => $salutationId,
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $customerId,
            'defaultPaymentMethodId' => $paymentMethodId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'not12345',
            'firstName' => $firstName,
            'lastName' => $lastName,
            'salutationId' => $salutationId,
            'customerNumber' => '12345',
        ];

        $customer = array_merge_recursive($customer, $options);

        $this->getContainer()->get('customer.repository')->create([$customer], Context::createDefaultContext());

        return $customerId;
    }

    private function fillCart(string $contextToken): void
    {
        $cart = $this->getContainer()->get(CartService::class)->createNew($contextToken);

        $productId = $this->createProduct();
        $cart->add(new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId));
        $cart->setTransactions($this->createTransaction());
    }

    private function createProduct(): string
    {
        $productId = Uuid::randomHex();

        $product = [
            'id' => $productId,
            'name' => 'Test product',
            'productNumber' => '123456789',
            'stock' => 1,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 19.99, 'net' => 10, 'linked' => false],
            ],
            'manufacturer' => ['id' => $productId, 'name' => 'laser AG'],
            'tax' => ['id' => $this->getValidTaxId(), 'name' => 'testTaxRate', 'taxRate' => 15],
            'categories' => [
                ['id' => $productId, 'name' => 'Test category'],
            ],
            'visibilities' => [
                [
                    'id' => $productId,
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $this->getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());

        return $productId;
    }

    private function createTransaction(): TransactionCollection
    {
        return new TransactionCollection([
            new Transaction(
                new CalculatedPrice(
                    13.37,
                    13.37,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                ),
                $this->getValidPaymentMethodId()
            ),
        ]);
    }

    private function setDomainForSalesChannel(string $domain, string $languageId): void
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        $data = [
            'id' => TestDefaults::SALES_CHANNEL,
            'domains' => [[
                'languageId' => $languageId,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => $domain,
            ]],
        ];

        $salesChannelRepository->update([$data], $this->salesChannelContext->getContext());
    }

    private function cleanDefaultSalesChannelDomain(): void
    {
        $connection = $this->getContainer()->get(Connection::class);

        $connection->delete(SalesChannelDomainDefinition::ENTITY_NAME, [
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
        ]);
    }
}