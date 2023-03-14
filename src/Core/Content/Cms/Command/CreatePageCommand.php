<?php declare(strict_types=1);

namespace Laser\Core\Content\Cms\Command;

use Faker\Factory;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('cms:page:create')]
#[Package('content')]
class CreatePageCommand extends Command
{
    /**
     * @var array<string>
     */
    private array $products;

    /**
     * @var array<string>
     */
    private array $categories;

    /**
     * @var array<string>
     */
    private array $media;

    public function __construct(
        private readonly EntityRepository $cmsPageRepository,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $mediaRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Reset all pages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('reset')) {
            $this->resetPages();
        }

        $faker = Factory::create();

        $page = [
            'id' => Uuid::randomHex(),
            'name' => $faker->company,
            'type' => 'landing_page',
            'blocks' => [
                [
                    'type' => 'image-text',
                    'slots' => [
                        ['type' => 'product-box', 'slot' => 'left', 'config' => ['productId' => $this->getRandomProductId()]],
                        ['type' => 'image', 'slot' => 'right', 'config' => ['url' => $this->getRandomImageUrl()]],
                    ],
                ],
                [
                    'type' => 'image-text',
                    'slots' => [
                        ['type' => 'text', 'slot' => 'left', 'config' => ['content' => $faker->realText()]],
                        ['type' => 'product-box', 'slot' => 'right', 'config' => ['productId' => $this->getRandomProductId()]],
                    ],
                ],
                [
                    'type' => 'image-text',
                    'slots' => [
                        ['type' => 'text', 'slot' => 'right', 'config' => ['content' => $faker->realText()]],
                        ['type' => 'image', 'slot' => 'left', 'config' => ['mediaId' => $this->getRandomMediaId()]],
                    ],
                ],
                [
                    'type' => 'listing',
                    'slots' => [
                        ['type' => 'product-listing', 'slot' => 'listing', 'config' => ['categoryId' => $this->getRandomCategoryId()]],
                    ],
                ],
            ],
        ];

        $this->cmsPageRepository->create([$page], Context::createDefaultContext());

        $output->writeln('ID: ' . $page['id']);

        return self::SUCCESS;
    }

    private function resetPages(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(999);

        $context = Context::createDefaultContext();
        $pages = $this->cmsPageRepository->searchIds($criteria, $context);

        if ($pages->getTotal() === 0) {
            return;
        }

        $keys = array_map(fn ($id) => ['id' => $id], $pages->getIds());

        $this->cmsPageRepository->delete($keys, $context);
    }

    private function getRandomImageUrl(): string
    {
        return 'https://source.unsplash.com/random?t=' . random_int(1, 9999);
    }

    private function getRandomProductId(): string
    {
        if (empty($this->products)) {
            $criteria = new Criteria();
            $criteria->setLimit(100);

            /** @var list<string> $productIds */
            $productIds = $this->productRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
            $this->products = $productIds;
        }

        return $this->products[array_rand($this->products)];
    }

    private function getRandomCategoryId(): string
    {
        if (empty($this->categories)) {
            $criteria = new Criteria();
            $criteria->setLimit(100);

            /** @var list<string> $categoryIds */
            $categoryIds = $this->categoryRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
            $this->categories = $categoryIds;
        }

        return $this->categories[array_rand($this->categories)];
    }

    private function getRandomMediaId(): string
    {
        if (empty($this->media)) {
            $criteria = new Criteria();
            $criteria->setLimit(100);

            /** @var list<string> $mediaIds */
            $mediaIds = $this->mediaRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
            $this->media = $mediaIds;
        }

        return $this->media[array_rand($this->media)];
    }
}