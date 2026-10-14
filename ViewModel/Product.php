<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\ViewModel;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Zero\GiftCard\Model\Config;

/**
 * A gift card product's form: its amounts (base currency, shown in the
 * visitor's).
 */
class Product implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * @return array<int, array{value: float, label: string}>
     */
    public function amounts(): array
    {
        $product = $this->registry->registry('current_product');
        if (!$product instanceof CatalogProduct) {
            return [];
        }

        return array_map(fn (float $amount) => [
            'value' => $amount,
            'label' => $this->priceCurrency->convertAndFormat($amount, false, 0),
        ], Config::amounts($product->getData(Config::AMOUNTS_ATTRIBUTE)));
    }
}
