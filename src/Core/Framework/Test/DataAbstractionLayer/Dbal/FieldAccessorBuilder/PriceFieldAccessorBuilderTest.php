<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer\Dbal\FieldAccessorBuilder;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\PriceFieldAccessorBuilder;
use Laser\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class PriceFieldAccessorBuilderTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var PriceFieldAccessorBuilder
     */
    protected $builder;

    public function setUp(): void
    {
        $this->builder = $this->getContainer()->get(PriceFieldAccessorBuilder::class);
    }

    public function testWithPriceAccessor(): void
    {
        $priceField = new PriceField('price', 'price');
        $context = Context::createDefaultContext();

        $sql = $this->builder->buildAccessor('product', $priceField, $context, 'price');

        static::assertSame('(ROUND((ROUND(CAST((COALESCE((JSON_UNQUOTE(JSON_EXTRACT(`product`.`price`, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.gross")) + 0.0))) as DECIMAL(30, 20)), 2)) * 100, 0) / 100)', $sql);
    }

    public function testWithListPriceAccessor(): void
    {
        $priceField = new PriceField('price', 'price');
        $context = Context::createDefaultContext();

        $sql = $this->builder->buildAccessor('product', $priceField, $context, 'price.listPrice');

        static::assertSame('(ROUND((ROUND(CAST((COALESCE((JSON_UNQUOTE(JSON_EXTRACT(`product`.`price`, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.listPrice.gross")) + 0.0))) as DECIMAL(30, 20)), 2)) * 100, 0) / 100)', $sql);
    }

    public function testWithPercentageAccessor(): void
    {
        $priceField = new PriceField('price', 'price');
        $context = Context::createDefaultContext();

        $sql = $this->builder->buildAccessor('product', $priceField, $context, 'price.percentage');

        static::assertSame('(ROUND((ROUND(CAST((COALESCE((JSON_UNQUOTE(JSON_EXTRACT(`product`.`price`, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.percentage.gross")) + 0.0))) as DECIMAL(30, 20)), 2)) * 100, 0) / 100)', $sql);
    }
}
