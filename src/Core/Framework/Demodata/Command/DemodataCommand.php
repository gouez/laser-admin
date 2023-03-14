<?php declare(strict_types=1);

namespace Laser\Core\Framework\Demodata\Command;

use Bezhanov\Faker\Provider\Commerce;
use Faker\Factory;
use Maltyxx\ImagesGenerator\ImagesGeneratorProvider;
use Laser\Core\Checkout\Customer\CustomerDefinition;
use Laser\Core\Checkout\Order\OrderDefinition;
use Laser\Core\Checkout\Promotion\PromotionDefinition;
use Laser\Core\Content\Category\CategoryDefinition;
use Laser\Core\Content\Flow\FlowDefinition;
use Laser\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterDefinition;
use Laser\Core\Content\MailTemplate\MailTemplateDefinition;
use Laser\Core\Content\Media\MediaDefinition;
use Laser\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Laser\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Laser\Core\Content\Product\ProductDefinition;
use Laser\Core\Content\ProductStream\ProductStreamDefinition;
use Laser\Core\Content\Property\PropertyGroupDefinition;
use Laser\Core\Content\Rule\RuleDefinition;
use Laser\Core\Framework\Adapter\Console\LaserStyle;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Demodata\DemodataRequest;
use Laser\Core\Framework\Demodata\DemodataService;
use Laser\Core\Framework\Demodata\Event\DemodataRequestCreatedEvent;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Laser\Core\System\Tag\TagDefinition;
use Laser\Core\System\User\UserDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'framework:demodata',
    description: 'Generates demo data',
)]
#[Package('core')]
class DemodataCommand extends Command
{
    /**
     * @var array<string, int>
     */
    private array $defaults = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly DemodataService $demodataService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $kernelEnv
    ) {
        parent::__construct();
    }

    public function addDefault(string $name, int $value): void
    {
        $this->defaults[$name] = $value;
    }

    protected function configure(): void
    {
        $this->addOption('product-attributes', null, InputOption::VALUE_OPTIONAL, 'Products attribute count');
        $this->addOption('manufacturer-attributes', null, InputOption::VALUE_OPTIONAL, 'Manufacturer attribute count');
        $this->addOption('order-attributes', null, InputOption::VALUE_OPTIONAL, 'Order attribute count');
        $this->addOption('customer-attributes', null, InputOption::VALUE_OPTIONAL, 'Customer attribute count');
        $this->addOption('media-attributes', null, InputOption::VALUE_OPTIONAL, 'Media attribute count');

        $this->addOption('reset-defaults', null, InputOption::VALUE_NONE, 'Set all counts to 0 unless specified');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ensureAllDependenciesArePresent();

        if ($this->kernelEnv !== 'prod') {
            $output->writeln('Demo data command should only be used in production environment. You can provide the environment as follows `APP_ENV=prod bin/console framework:demodata`');

            return self::INVALID;
        }

        $io = new LaserStyle($input, $output);
        $io->title('Demodata Generator');

        $context = Context::createDefaultContext();

        $request = new DemodataRequest();

        $request->add(TagDefinition::class, $this->getCount($input, 'tags'));
        $request->add(RuleDefinition::class, $this->getCount($input, 'rules'));
        $request->add(MediaDefinition::class, $this->getCount($input, 'media'));
        $request->add(CustomerDefinition::class, $this->getCount($input, 'customers'));
        $request->add(PropertyGroupDefinition::class, $this->getCount($input, 'properties'));
        $request->add(CategoryDefinition::class, $this->getCount($input, 'categories'));
        $request->add(ProductManufacturerDefinition::class, $this->getCount($input, 'manufacturers'));
        $request->add(ProductDefinition::class, $this->getCount($input, 'products'));
        $request->add(ProductStreamDefinition::class, $this->getCount($input, 'product-streams'));
        $request->add(PromotionDefinition::class, $this->getCount($input, 'promotions'));
        $request->add(OrderDefinition::class, $this->getCount($input, 'orders'));
        $request->add(ProductReviewDefinition::class, $this->getCount($input, 'reviews'));
        $request->add(UserDefinition::class, $this->getCount($input, 'users'));
        $request->add(FlowDefinition::class, $this->getCount($input, 'flows'));

        $request->add(
            CustomFieldSetDefinition::class,
            $this->getCount($input, 'attribute-sets'),
            $this->getCustomFieldOptions($input)
        );

        $request->add(MailTemplateDefinition::class, $this->getCount($input, 'mail-template'));
        $request->add(MailHeaderFooterDefinition::class, $this->getCount($input, 'mail-header-footer'));

        $this->eventDispatcher->dispatch(new DemodataRequestCreatedEvent($request, $context, $input));

        $demoContext = $this->demodataService->generate($request, $context, $io);

        $io->table(
            ['Entity', 'Items', 'Time'],
            $demoContext->getTimings()
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function getCustomFieldOptions(InputInterface $input): array
    {
        return [
            'relations' => [
                'product' => $this->getCount($input, 'product-attributes'),
                'product_manufacturer' => $this->getCount($input, 'manufacturer-attributes'),
                'order' => $this->getCount($input, 'order-attributes'),
                'customer' => $this->getCount($input, 'customer-attributes'),
                'media' => $this->getCount($input, 'media-attributes'),
            ],
        ];
    }

    private function getCount(InputInterface $input, string $name): int
    {
        if ($input->hasOption($name) && $input->getOption($name) !== null) {
            return (int) $input->getOption($name);
        }

        if ($input->getOption('reset-defaults')) {
            return 0;
        }

        return $this->defaults[$name] ?? 0;
    }

    /**
     * @codeCoverageIgnore
     */
    private function ensureAllDependenciesArePresent(): void
    {
        $classes = [Factory::class, Commerce::class, ImagesGeneratorProvider::class];

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw new \RuntimeException('Please install composer package "laser/dev-tools" to use the demo-data command.');
            }
        }
    }
}
