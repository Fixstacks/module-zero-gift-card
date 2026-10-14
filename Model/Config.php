<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /** Product attribute: the amounts a gift card product sells ("25, 50, 100"). */
    public const AMOUNTS_ATTRIBUTE = 'zero_giftcard_amounts';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag('zero_giftcard/general/enabled', ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    public function expireDays(?int $websiteId = null): int
    {
        return max(0, (int) $this->scopeConfig->getValue('zero_giftcard/general/expire_days', ScopeInterface::SCOPE_WEBSITE, $websiteId));
    }

    public function identity(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue('zero_giftcard/general/identity', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function template(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue('zero_giftcard/general/template', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * A product's amounts (base currency), smallest first; none: not a gift card.
     *
     * @return float[]
     */
    public static function amounts(mixed $value): array
    {
        $amounts = [];
        foreach (preg_split('/[\s,;]+/', (string) $value) ?: [] as $part) {
            if (is_numeric($part) && (float) $part > 0) {
                $amounts[] = round((float) $part, 2);
            }
        }
        $amounts = array_values(array_unique($amounts));
        sort($amounts);

        return $amounts;
    }
}
