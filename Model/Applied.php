<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model;

use Magento\Framework\DataObject;

/**
 * The cards on a cart or order, kept as JSON: the codes a cart asks to pay
 * with (zero_giftcard_codes), and what each paid (zero_giftcard_used:
 * code, card_id, base, amount).
 */
class Applied
{
    /**
     * @return string[]
     */
    public static function codes(DataObject $quote): array
    {
        $codes = json_decode((string) $quote->getData('zero_giftcard_codes'), true);

        return is_array($codes) ? array_values(array_filter($codes, 'is_string')) : [];
    }

    /**
     * @param string[] $codes
     */
    public static function setCodes(DataObject $quote, array $codes): void
    {
        $codes = array_values(array_unique($codes));
        $quote->setData('zero_giftcard_codes', $codes ? json_encode($codes) : null);
    }

    /**
     * @return array<int, array{code: string, card_id: int, base: float, amount: float}>
     */
    public static function used(DataObject $object): array
    {
        $used = json_decode((string) $object->getData('zero_giftcard_used'), true);

        return is_array($used) ? $used : [];
    }
}
