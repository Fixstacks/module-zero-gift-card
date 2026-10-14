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
 * A cancelled order gives the cards back what they paid and no invoice took.
 */
class ReturnOnCancel implements ObserverInterface
{
    public function __construct(private readonly OrderCards $orderCards)
    {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        $open = (float) $order->getData('base_zero_giftcard_amount')
            - (float) $order->getData('base_zero_giftcard_invoiced')
            - (float) $order->getData('base_zero_giftcard_returned');
        if ($open > 0.0001) {
            $given = $this->orderCards->giveBack($order, $open, Cards::RETURNED, (string) __('Order cancelled'));
            $order->setData('base_zero_giftcard_returned', (float) $order->getData('base_zero_giftcard_returned') + $given);
        }
    }
}
