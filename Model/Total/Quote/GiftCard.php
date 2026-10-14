<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model\Total\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Zero\GiftCard\Model\Applied;
use Zero\GiftCard\Model\Cards;
use Zero\GiftCard\Model\Config;

/**
 * The cart's gift cards pay its grand total (shipping and tax included), in
 * the order they were added, each up to what is left on it. Cards bought in
 * the same cart can't pay for themselves (they don't exist yet). What each
 * pays lands on the quote (zero_giftcard_used), then the order.
 */
class GiftCard extends AbstractTotal
{
    public function __construct(
        private readonly Cards $cards,
        private readonly Config $config,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
        $this->setCode('zero_giftcard');
    }

    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);
        if (!$shippingAssignment->getItems()) {
            return $this;
        }
        $quote->setData('zero_giftcard_used', null)->setData('zero_giftcard_amount', 0)->setData('base_zero_giftcard_amount', 0);
        $codes = Applied::codes($quote);
        $websiteId = (int) $quote->getStore()->getWebsiteId();
        if (!$codes || !$this->config->isEnabled($websiteId)) {
            return $this;
        }
        $leftBase = (float) $total->getBaseGrandTotal();
        $left = (float) $total->getGrandTotal();
        $used = [];
        $sumBase = 0.0;
        $sum = 0.0;
        foreach ($codes as $code) {
            if ($leftBase <= 0.0001) {
                break;
            }
            $card = $this->cards->byCode($code);
            if ($this->cards->problem($card, $websiteId) !== null) {
                continue;
            }
            $base = round(min((float) $card['balance'], $leftBase), 2);
            $amount = $base >= $leftBase - 0.0001 ? $left : min($left, $this->priceCurrency->convertAndRound($base, $quote->getStore()));
            $used[] = ['code' => (string) $card['code'], 'card_id' => (int) $card['card_id'], 'base' => $base, 'amount' => $amount];
            $leftBase -= $base;
            $left -= $amount;
            $sumBase += $base;
            $sum += $amount;
        }
        if (!$used) {
            return $this;
        }
        $total->setBaseGrandTotal(max(0.0, (float) $total->getBaseGrandTotal() - $sumBase));
        $total->setGrandTotal(max(0.0, (float) $total->getGrandTotal() - $sum));
        $quote->setData('zero_giftcard_used', json_encode($used))
            ->setData('zero_giftcard_amount', $sum)
            ->setData('base_zero_giftcard_amount', $sumBase);

        return $this;
    }

    public function fetch(Quote $quote, Total $total)
    {
        $amount = (float) $quote->getData('zero_giftcard_amount');

        return $amount > 0 ? ['code' => $this->getCode(), 'title' => __('Gift card'), 'value' => -$amount] : null;
    }
}
