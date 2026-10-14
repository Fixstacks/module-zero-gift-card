<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model;

use Magento\Sales\Model\Order;

/**
 * Money going back to an order's cards (a cancel, a refund): to the cards
 * that paid, the last one first, no card more than it paid.
 */
class OrderCards
{
    public function __construct(private readonly Cards $cards)
    {
    }

    /**
     * @return float Given back (base)
     */
    public function giveBack(Order $order, float $base, string $action, string $comment): float
    {
        $given = 0.0;
        $incrementId = (string) $order->getIncrementId();
        foreach (array_reverse(Applied::used($order)) as $use) {
            if ($base - $given <= 0.0001) {
                break;
            }
            $open = $this->cards->used((int) $use['card_id'], $incrementId);
            $back = round(min($open, $base - $given), 2);
            if ($back > 0) {
                $this->cards->change((int) $use['card_id'], $back, $action, $incrementId, $comment);
                $given += $back;
            }
        }

        return $given;
    }
}
