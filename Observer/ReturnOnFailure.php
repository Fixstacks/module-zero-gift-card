<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zero\GiftCard\Model\Cards;
use Zero\GiftCard\Model\OrderCards;

/**
 * The order failed to place after the cards paid: they get it back.
 */
class ReturnOnFailure implements ObserverInterface
{
    public function __construct(private readonly OrderCards $orderCards)
    {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        $base = (float) $order?->getData('base_zero_giftcard_amount');
        if ($base > 0) {
            $this->orderCards->giveBack($order, $base, Cards::RETURNED, (string) __('The order was not placed.'));
        }
    }
}
