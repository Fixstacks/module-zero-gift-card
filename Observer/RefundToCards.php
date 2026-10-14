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
 * A refund's card share goes back onto the cards.
 */
class RefundToCards implements ObserverInterface
{
    public function __construct(private readonly OrderCards $orderCards)
    {
    }

    public function execute(Observer $observer): void
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        $base = (float) $creditmemo->getData('base_zero_giftcard_amount');
        if ($base > 0) {
            $given = $this->orderCards->giveBack($order, $base, Cards::REFUNDED, (string) __('Refund'));
            $order->setData('base_zero_giftcard_refunded', (float) $order->getData('base_zero_giftcard_refunded') + $given);
        }
    }
}
