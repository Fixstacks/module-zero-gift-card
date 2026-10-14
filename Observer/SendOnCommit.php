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
use Zero\GiftCard\Model\Mailer;

/**
 * Once the invoice is saved: the order's cards not yet emailed go out (to
 * the recipient, or to the buyer).
 */
class SendOnCommit implements ObserverInterface
{
    public function __construct(
        private readonly Cards $cards,
        private readonly Mailer $mailer
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getInvoice()?->getOrder();
        if (!$order) {
            return;
        }
        foreach ($this->cards->forOrder((string) $order->getIncrementId()) as $card) {
            if ($card['sent_at'] === null && $card['order_item_id'] !== null) {
                $this->mailer->send($card, (string) $order->getCustomerEmail(), trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()));
            }
        }
    }
}
