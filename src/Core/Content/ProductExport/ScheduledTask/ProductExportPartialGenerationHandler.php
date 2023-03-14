<?php declare(strict_types=1);

namespace Laser\Core\Content\ProductExport\ScheduledTask;

use Doctrine\DBAL\Connection;
use Laser\Core\Content\ProductExport\ProductExportEntity;
use Laser\Core\Content\ProductExport\Service\ProductExportFileHandlerInterface;
use Laser\Core\Content\ProductExport\Service\ProductExportGeneratorInterface;
use Laser\Core\Content\ProductExport\Service\ProductExportRendererInterface;
use Laser\Core\Content\ProductExport\Struct\ExportBehavior;
use Laser\Core\Content\ProductExport\Struct\ProductExportResult;
use Laser\Core\Defaults;
use Laser\Core\Framework\Adapter\Translation\Translator;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\Locale\LanguageLocaleCodeProvider;
use Laser\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextService;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('sales-channel')]
final class ProductExportPartialGenerationHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductExportGeneratorInterface $productExportGenerator,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly EntityRepository $productExportRepository,
        private readonly ProductExportFileHandlerInterface $productExportFileHandler,
        private readonly MessageBusInterface $messageBus,
        private readonly ProductExportRendererInterface $productExportRender,
        private readonly Translator $translator,
        private readonly SalesChannelContextServiceInterface $salesChannelContextService,
        private readonly SalesChannelContextPersister $contextPersister,
        private readonly Connection $connection,
        private readonly int $readBufferSize,
        private readonly LanguageLocaleCodeProvider $languageLocaleProvider
    ) {
    }

    public function __invoke(ProductExportPartialGeneration $productExportPartialGeneration): void
    {
        $context = $this->getContext($productExportPartialGeneration);
        $productExport = $this->fetchProductExport($productExportPartialGeneration, $context);

        if (!$productExport) {
            return;
        }

        $exportResult = $this->runExport($productExport, $productExportPartialGeneration->getOffset(), $context);

        $filePath = $this->productExportFileHandler->getFilePath($productExport, true);

        if ($exportResult === null) {
            $this->finalizeExport($productExport, $filePath);

            return;
        }

        $this->productExportFileHandler->writeProductExportContent(
            $exportResult->getContent(),
            $filePath,
            $productExportPartialGeneration->getOffset() > 0
        );

        if ($productExportPartialGeneration->getOffset() + $this->readBufferSize < $exportResult->getTotal()) {
            $this->messageBus->dispatch(
                new ProductExportPartialGeneration(
                    $productExportPartialGeneration->getProductExportId(),
                    $productExportPartialGeneration->getSalesChannelId(),
                    $productExportPartialGeneration->getOffset() + $this->readBufferSize
                )
            );

            return;
        }

        $this->finalizeExport($productExport, $filePath);
    }

    private function getContext(ProductExportPartialGeneration $productExportPartialGeneration): Context
    {
        $context = $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $productExportPartialGeneration->getSalesChannelId()
        );

        if ($context->getSalesChannel()->getTypeId() !== Defaults::SALES_CHANNEL_TYPE_STOREFRONT) {
            throw new SalesChannelNotFoundException();
        }

        return $context->getContext();
    }

    private function fetchProductExport(
        ProductExportPartialGeneration $productExportPartialGeneration,
        Context $context
    ): ?ProductExportEntity {
        $criteria = new Criteria([$productExportPartialGeneration->getProductExportId()]);
        $criteria
            ->addAssociation('salesChannel')
            ->addAssociation('salesChannelDomain.salesChannel')
            ->addAssociation('salesChannelDomain.language.locale')
            ->addAssociation('productStream.filters.queries')
            ->setLimit(1);

        return $this->productExportRepository
            ->search($criteria, $context)
            ->first();
    }

    private function runExport(
        ProductExportEntity $productExport,
        int $offset,
        Context $context
    ): ?ProductExportResult {
        $this->productExportRepository->update([[
            'id' => $productExport->getId(),
            'isRunning' => true,
        ]], $context);

        return $this->productExportGenerator->generate(
            $productExport,
            new ExportBehavior(
                false,
                false,
                true,
                false,
                false,
                $offset
            )
        );
    }

    private function finalizeExport(ProductExportEntity $productExport, string $filePath): void
    {
        $contextToken = Uuid::randomHex();
        $this->contextPersister->save(
            $contextToken,
            [
                SalesChannelContextService::CURRENCY_ID => $productExport->getCurrencyId(),
            ],
            $productExport->getSalesChannelId()
        );

        $context = $this->salesChannelContextService->get(
            new SalesChannelContextServiceParameters(
                $productExport->getStorefrontSalesChannelId(),
                $contextToken,
                $productExport->getSalesChannelDomain()->getLanguageId(),
                $productExport->getSalesChannelDomain()->getCurrencyId() ?? $productExport->getCurrencyId()
            )
        );

        $this->translator->injectSettings(
            $productExport->getStorefrontSalesChannelId(),
            $productExport->getSalesChannelDomain()->getLanguageId(),
            $this->languageLocaleProvider->getLocaleForLanguageId($productExport->getSalesChannelDomain()->getLanguageId()),
            $context->getContext()
        );

        $headerContent = $this->productExportRender->renderHeader($productExport, $context);
        $footerContent = $this->productExportRender->renderFooter($productExport, $context);
        $finalFilePath = $this->productExportFileHandler->getFilePath($productExport);

        $this->translator->resetInjection();

        $writeProductExportSuccessful = $this->productExportFileHandler->finalizePartialProductExport(
            $filePath,
            $finalFilePath,
            $headerContent,
            $footerContent
        );

        $this->connection->delete('sales_channel_api_context', ['token' => $contextToken]);

        if (!$writeProductExportSuccessful) {
            return;
        }

        $this->productExportRepository->update(
            [
                [
                    'id' => $productExport->getId(),
                    'generatedAt' => new \DateTime(),
                    'isRunning' => false,
                ],
            ],
            $context->getContext()
        );
    }
}