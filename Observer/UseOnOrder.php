<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zero\GiftCard\Model\Applied;
use Zero\GiftCard\Model\Cards;

/**
 * Placing an order paid partly or all with cards: what each pays moves to
 * the order and leaves the card; a card short of it stops the order.
 */
class UseOnOrder implements ObserverInterface
{
    public function __construct(private readonly Cards $cards)
    {
    }

    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();
        $used = Applied::used($quote);
        if (!$used) {
            return;
        }
        $order->setData('zero_giftcard_used', $quote->getData('zero_giftcard_used'))
            ->setData('zero_giftcard_amount', (float) $quote->getData('zero_giftcard_amount'))
            ->setData('base_zero_giftcard_amount', (float) $quote->getData('base_zero_giftcard_amount'));
        foreach ($used as $use) {
            $this->cards->change((int) $use['card_id'], -(float) $use['base'], Cards::USED, (string) $order->getIncrementId());
        }
    }
}
