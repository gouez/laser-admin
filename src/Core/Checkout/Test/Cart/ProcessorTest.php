<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Cart;

use PHPUnit\Framework\TestCase;
use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\CartBehavior;
use Laser\Core\Checkout\Cart\CartDataCollectorInterface;
use Laser\Core\Checkout\Cart\CartProcessorInterface;
use Laser\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Laser\Core\Checkout\Cart\Error\Error;
use Laser\Core\Checkout\Cart\LineItem\CartDataCollection;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Checkout\Cart\Price\AmountCalculator;
use Laser\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Laser\Core\Checkout\Cart\Processor;
use Laser\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Laser\Core\Checkout\Cart\Transaction\TransactionProcessor;
use Laser\Core\Checkout\Cart\Validator;
use Laser\Core\Checkout\Promotion\Cart\Error\AutoPromotionNotFoundError;
use Laser\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Laser\Core\Defaults;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Script\Execution\ScriptExecutor;
use Laser\Core\Framework\Struct\Struct;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Laser\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
class ProcessorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use TaxAddToSalesChannelTestBehaviour;

    private AbstractSalesChannelContextFactory $factory;

    private SalesChannelContext $context;

    private Processor $processor;

    protected function setUp(): void
    {
        $this->processor = $this->getContainer()->get(Processor::class);
        $this->factory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->context = $this->factory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    public function testDeliveryCreatedForDeliverableLineItem(): void
    {
        $cart = new Cart('test');

        $id = Uuid::randomHex();
        $tax = ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'test'];

        $product = [
            'id' => $id,
            'name' => 'test',
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 119.99, 'net' => 99.99, 'linked' => false],
            ],
            'productNumber' => Uuid::randomHex(),
            'manufacturer' => ['name' => 'test'],
            'tax' => $tax,
            'stock' => 10,
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        $this->getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $this->addTaxDataToSalesChannel($this->context, $tax);

        $cart->add(
            (new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, $id, 1))
                ->setStackable(true)
                ->setRemovable(true)
        );

        $calculated = $this->processor->process($cart, $this->context, new CartBehavior());

        static::assertCount(1, $calculated->getLineItems());
        static::assertTrue($calculated->has($id));
        $item = $calculated->get($id);
        static::assertInstanceOf(LineItem::class, $item);
        static::assertInstanceOf(CalculatedPrice::class, $item->getPrice());
        static::assertSame(119.99, $item->getPrice()->getTotalPrice());

        static::assertCount(1, $calculated->getDeliveries());

        /** @var Delivery $delivery */
        $delivery = $calculated->getDeliveries()->first();
        static::assertTrue($delivery->getPositions()->getLineItems()->has($id));
    }

    public function testExtensionsAreMergedEarly(): void
    {
        $extension = new class() extends Struct {
        };

        $cart = new Cart('bar');
        $cart->addExtension('unit-test', $extension);

        $processor = new Processor(
            new Validator([]),
            $this->createMock(AmountCalculator::class),
            $this->createMock(TransactionProcessor::class),
            [
                new class() implements CartProcessorInterface {
                    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
                    {
                        TestCase::assertNotEmpty($original->getExtension('unit-test'));
                        TestCase::assertNotEmpty($toCalculate->getExtension('unit-test'));
                        TestCase::assertSame($original->getExtension('unit-test'), $toCalculate->getExtension('unit-test'));
                    }
                },
            ],
            [],
            $this->createMock(ScriptExecutor::class)
        );

        $newCart = $processor->process($cart, $this->context, new CartBehavior());

        static::assertSame($extension, $newCart->getExtension('unit-test'));
    }

    public function testCalculatedCreditTaxesIncludeCustomItemTax(): void
    {
        $cart = new Cart('test');

        $productId = Uuid::randomHex();
        $customItemId = Uuid::randomHex();
        $creditId = Uuid::randomHex();

        $taxForProductItem = 10;

        $tax = ['id' => Uuid::randomHex(), 'taxRate' => $taxForProductItem, 'name' => 'test'];
        $product = [
            'id' => $productId,
            'name' => 'test',
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 220, 'net' => 200, 'linked' => false],
            ],
            'productNumber' => Uuid::randomHex(),
            'manufacturer' => ['name' => 'test'],
            'tax' => $tax,
            'stock' => 10,
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        $this->getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $this->addTaxDataToSalesChannel($this->context, $tax);

        $taxForCustomItem = 20;

        $productLineItem = new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 1);
        $taxRulesCustomItem = new TaxRuleCollection([new TaxRule($taxForCustomItem)]);
        $customLineItem = (new LineItem($customItemId, LineItem::CUSTOM_LINE_ITEM_TYPE, $customItemId, 1))
            ->setLabel('custom')
            ->setPriceDefinition(new QuantityPriceDefinition(200, $taxRulesCustomItem, 2));

        $creditLineItem = (new LineItem($creditId, LineItem::CREDIT_LINE_ITEM_TYPE, $creditId, 1))
            ->setLabel('credit')
            ->setPriceDefinition(new AbsolutePriceDefinition(-100));

        $cart->addLineItems(new LineItemCollection([$productLineItem, $customLineItem, $creditLineItem]));

        $calculated = $this->processor->process($cart, $this->context, new CartBehavior());

        static::assertCount(3, $calculated->getLineItems());

        $creditLineItem = $calculated->getLineItems()->filterType(LineItem::CREDIT_LINE_ITEM_TYPE)->first();
        static::assertInstanceOf(LineItem::class, $creditLineItem);
        static::assertInstanceOf(CalculatedPrice::class, $creditLineItem->getPrice());
        static::assertCount(2, $creditCalculatedTaxes = $creditLineItem->getPrice()->getCalculatedTaxes()->getElements());

        $calculatedTaxForCustomItem = array_filter($creditCalculatedTaxes, fn (CalculatedTax $tax) => (int) $tax->getTaxRate() === $taxForCustomItem);

        static::assertNotEmpty($calculatedTaxForCustomItem);
        static::assertCount(1, $calculatedTaxForCustomItem);

        $calculatedTaxForProductItem = array_filter($creditCalculatedTaxes, fn (CalculatedTax $tax) => (int) $tax->getTaxRate() === $taxForProductItem);

        static::assertNotEmpty($calculatedTaxForProductItem);
        static::assertCount(1, $calculatedTaxForProductItem);
    }

    public function testShippingCostIsCalculatedWithCustomItemOnly(): void
    {
        $cart = new Cart('test');

        $customItemId = Uuid::randomHex();

        $tax = ['id' => Uuid::randomHex(), 'taxRate' => 10, 'name' => 'test'];

        $this->addTaxDataToSalesChannel($this->context, $tax);

        $taxForCustomItem = 20;
        $taxRulesCustomItem = new TaxRuleCollection([new TaxRule($taxForCustomItem)]);

        $customLineItem = (new LineItem($customItemId, LineItem::CUSTOM_LINE_ITEM_TYPE, $customItemId, 1))
            ->setLabel('custom')
            ->setPriceDefinition(new QuantityPriceDefinition(200, $taxRulesCustomItem, 2));

        $cart->add($customLineItem);

        $calculated = $this->processor->process($cart, $this->context, new CartBehavior());

        $delivery = $calculated->getDeliveries()->first();

        static::assertInstanceOf(Delivery::class, $delivery);

        $shippingCalculatedTaxes = $delivery->getShippingCosts()->getCalculatedTaxes()->first();
        static::assertInstanceOf(CalculatedTax::class, $shippingCalculatedTaxes);
        static::assertEquals($taxForCustomItem, $shippingCalculatedTaxes->getTaxRate());
    }

    public function testShippingCostCalculatedTaxesIncludeCustomItemTax(): void
    {
        $cart = new Cart('test');

        $productId = Uuid::randomHex();
        $customItemId = Uuid::randomHex();

        $taxForCustomItem = 20;
        $taxForProductItem = 10;

        $tax = ['id' => Uuid::randomHex(), 'taxRate' => $taxForProductItem, 'name' => 'test'];

        $product = [
            'id' => $productId,
            'name' => 'test',
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 220, 'net' => 200, 'linked' => false],
            ],
            'productNumber' => Uuid::randomHex(),
            'manufacturer' => ['name' => 'test'],
            'tax' => $tax,
            'stock' => 10,
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        $this->getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $this->addTaxDataToSalesChannel($this->context, $tax);

        $taxRulesCustomItem = new TaxRuleCollection([new TaxRule($taxForCustomItem)]);

        $productLineItem = new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 1);
        $customLineItem = (new LineItem($customItemId, LineItem::CUSTOM_LINE_ITEM_TYPE, $customItemId, 1))
            ->setLabel('custom')
            ->setPriceDefinition(new QuantityPriceDefinition(200, $taxRulesCustomItem, 2));

        $cart->add($productLineItem);
        $cart->add($customLineItem);

        $calculated = $this->processor->process($cart, $this->context, new CartBehavior());

        static::assertCount(1, $calculated->getDeliveries());

        $delivery = $calculated->getDeliveries()->first();

        static::assertInstanceOf(Delivery::class, $delivery);
        static::assertCount(2, $shippingCalculatedTaxes = $delivery->getShippingCosts()->getCalculatedTaxes()->getElements());

        $calculatedTaxForCustomItem = array_filter($shippingCalculatedTaxes, fn (CalculatedTax $tax) => (int) $tax->getTaxRate() === $taxForCustomItem);

        static::assertNotEmpty($calculatedTaxForCustomItem);
        static::assertCount(1, $calculatedTaxForCustomItem);

        $calculatedTaxForProductItem = array_filter($shippingCalculatedTaxes, fn (CalculatedTax $tax) => (int) $tax->getTaxRate() === $taxForProductItem);

        static::assertNotEmpty($calculatedTaxForProductItem);
        static::assertCount(1, $calculatedTaxForProductItem);
    }

    public function testPersistentErrors(): void
    {
        $cart = new Cart(Uuid::randomHex());

        $cart->addErrors(new NonePersistentError(), new PersistentError());

        $cart = $this->getContainer()->get(Processor::class)
            ->process($cart, $this->context, new CartBehavior());

        static::assertCount(1, $cart->getErrors());
        static::assertInstanceOf(PersistentError::class, $cart->getErrors()->first());

        $error = $cart->getErrors()->first();
        static::assertEquals('persistent', $error->getId());
        static::assertEquals('persistent', $error->getMessageKey());
    }

    public function testCartHasErrorDataAddedFromPromotionProcessor(): void
    {
        $originalCart = new Cart(Uuid::randomHex());

        $id = Uuid::randomHex();
        $tax = ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'test'];

        $product = $this->createDummyProduct($id, $tax);

        $this->getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $this->addTaxDataToSalesChannel($this->context, $tax);

        $originalCart->add(
            (new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, $id, 1))
                ->setStackable(true)
                ->setRemovable(true)
        );
        $originalCart->add(
            (new LineItem(Uuid::randomHex(), LineItem::PROMOTION_LINE_ITEM_TYPE, '', 1))
                ->setLabel('Discount 15%')
        );
        $originalCart->add(
            (new LineItem(Uuid::randomHex(), LineItem::PROMOTION_LINE_ITEM_TYPE, '', 1))
                ->setLabel('Discount 10%')
        );

        $this->processor->process($originalCart, $this->context, new CartBehavior());
        foreach ($originalCart->getErrors() as $error) {
            static::assertInstanceOf(AutoPromotionNotFoundError::class, $error);
        }
    }

    public function testProcessorsAndCollectorsAreSkippedIfCartIsEmpty(): void
    {
        $cart = new Cart('test');

        $collector = $this->createMock(CartDataCollectorInterface::class);
        $collector->expects(static::never())
            ->method('collect');

        $processorMock = $this->createMock(CartProcessorInterface::class);
        $processorMock->expects(static::never())
            ->method('process');

        $processor = new Processor(
            $this->getContainer()->get(Validator::class),
            $this->getContainer()->get(AmountCalculator::class),
            $this->getContainer()->get(TransactionProcessor::class),
            [$processorMock],
            [$collector],
            $this->getContainer()->get(ScriptExecutor::class)
        );
        $processor->process($cart, $this->context, new CartBehavior());
    }

    /**
     * @param array<string|int, mixed|null> $tax
     *
     * @return array<string, mixed|null>
     */
    private function createDummyProduct(string $id, array $tax, int $stock = 10): array
    {
        return [
            'id' => $id,
            'name' => 'test',
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 119.99, 'net' => 99.99, 'linked' => false],
            ],
            'productNumber' => Uuid::randomHex(),
            'manufacturer' => ['name' => 'test'],
            'tax' => $tax,
            'stock' => $stock,
            'isCloseout' => true,
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];
    }
}

/**
 * @internal
 */
class PersistentError extends Error
{
    public function getId(): string
    {
        return 'persistent';
    }

    public function getMessageKey(): string
    {
        return 'persistent';
    }

    public function getLevel(): int
    {
        return 1;
    }

    public function blockOrder(): bool
    {
        return false;
    }

    /**
     * @return array<string|int, mixed|null>
     */
    public function getParameters(): array
    {
        return [];
    }

    public function isPersistent(): bool
    {
        return true;
    }
}

/**
 * @internal
 */
class NonePersistentError extends PersistentError
{
    public function getId(): string
    {
        return 'none-' . parent::getId();
    }

    public function getMessageKey(): string
    {
        return 'none-' . parent::getMessageKey();
    }

    public function isPersistent(): bool
    {
        return false;
    }
}