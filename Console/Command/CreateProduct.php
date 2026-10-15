<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Console\Command;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zero\GiftCard\Model\Config;

/**
 * zero:giftcard:create-product: a gift card product (virtual, no tax, in
 * every website, in the catalog and search), unless its SKU exists.
 */
class CreateProduct extends Command
{
    public function __construct(
        private readonly ProductInterfaceFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $state,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('zero:giftcard:create-product')
            ->setDescription('Creates a gift card product to sell')
            ->addOption('sku', null, InputOption::VALUE_REQUIRED, 'SKU', 'zero-gift-card')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name', 'Gift Card')
            ->addOption('amounts', null, InputOption::VALUE_REQUIRED, 'Amounts, base currency', '25, 50, 100, 200');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Throwable) {
        }
        $sku = (string) $input->getOption('sku');
        try {
            $product = $this->productRepository->get($sku);
            if (in_array((string) $product->getImage(), ['', 'no_selection'], true)) {
                $this->addImage($product);
                $this->productRepository->save($product);
                $output->writeln(sprintf('<info>%s exists; its image is added.</info>', $sku));
            } else {
                $output->writeln(sprintf('<info>%s exists.</info>', $sku));
            }

            return Command::SUCCESS;
        } catch (NoSuchEntityException) {
        }
        $amounts = Config::amounts($input->getOption('amounts'));
        if (!$amounts) {
            $output->writeln('<error>Give amounts, e.g. --amounts="25, 50, 100".</error>');

            return Command::FAILURE;
        }
        $product = $this->productFactory->create();
        $product->setSku($sku)
            ->setName((string) $input->getOption('name'))
            ->setTypeId(Type::TYPE_VIRTUAL)
            ->setAttributeSetId($product->getDefaultAttributeSetId())
            ->setPrice($amounts[0])
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setTaxClassId(0)
            ->setWebsiteIds(array_map(static fn ($website) => (int) $website->getId(), $this->storeManager->getWebsites()))
            ->setStockData(['use_config_manage_stock' => 0, 'manage_stock' => 0, 'is_in_stock' => 1])
            ->setData('description', '<p>A gift card for the store: choose the amount, who it is for and a message. It is emailed once the order is paid.</p>')
            ->setData(Config::AMOUNTS_ATTRIBUTE, implode(', ', $amounts))
            ->setData('url_key', $sku);
        $this->addImage($product);
        $this->productRepository->save($product);
        $output->writeln(sprintf('<info>%s created: %s.</info> It shows once the indexers have run (cron, or bin/magento indexer:reindex).', $sku, implode(', ', $amounts)));

        return Command::SUCCESS;
    }

    /**
     * The module's gift card picture (Setup/fixtures/gift-card.jpg) as the
     * product's image, so the product doesn't show Magento's placeholder.
     * Replace it in the admin like any product image. The gallery only takes
     * files from under pub/media, so the picture is copied there first.
     */
    private function addImage(\Magento\Catalog\Model\Product $product): void
    {
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $file = 'zero/giftcard/gift-card.jpg';
        $media->writeFile($file, (string) file_get_contents(dirname(__DIR__, 2) . '/Setup/fixtures/gift-card.jpg'));
        $product->addImageToMediaGallery($media->getAbsolutePath($file), ['image', 'small_image', 'thumbnail'], false, false);
    }
}
