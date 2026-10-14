<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zero\GiftCard\Model\Config;

/**
 * "Gift Card Amounts" on virtual products, in every attribute set: set,
 * the product is a gift card sold in those amounts.
 */
class AddAmountsAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(Product::ENTITY, Config::AMOUNTS_ATTRIBUTE, [
            'type' => 'varchar',
            'label' => 'Gift Card Amounts',
            'input' => 'text',
            'note' => 'Set on a virtual product to sell it as a gift card in these amounts (base currency), e.g. 25, 50, 100.',
            'required' => false,
            'global' => Attribute::SCOPE_WEBSITE,
            'visible' => true,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'apply_to' => 'virtual',
            'group' => 'General',
            'sort_order' => 200,
        ]);
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
