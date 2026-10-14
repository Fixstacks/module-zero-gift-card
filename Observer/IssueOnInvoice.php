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
use Zero\GiftCard\Model\Config;
use Zero\GiftCard\Model\Purchase;

/**
 * Paid: a card for each gift card bought on the invoice, worth what it cost
 * (base currency). Emailed once the invoice is saved (SendOnCommit).
 */
class IssueOnInvoice implements ObserverInterface
{
    public function __construct(private readonly Cards $cards)
    {
    }

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        foreach ($invoice->getAllItems() as $invoiceItem) {
            $item = $invoiceItem->getOrderItem();
            $request = $item?->getProductOptionByCode('info_buyRequest');
            if (!$item || !is_array($request) || !isset($request['giftcard']) || $item->getParentItem()) {
                continue;
            }
            try {
                $card = Purchase::read($request['giftcard'], Config::amounts($request['giftcard']['amount'] ?? ''));
            } catch (\Throwable) {
                continue;
            }
            $have = count($this->cards->forOrder((string) $order->getIncrementId(), (int) $item->getId()));
            $make = (int) round((float) $item->getQtyInvoiced()) - $have;
            for ($i = 0; $i < $make; $i++) {
                $this->cards->create((float) $item->getBasePrice(), (int) $order->getStore()->getWebsiteId(), [
                    'sender_name' => $card['sender_name'] ?: trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()),
                    'recipient_name' => $card['recipient_name'],
                    'recipient_email' => $card['recipient_email'],
                    'message' => $card['message'],
                    'store_id' => (int) $order->getStoreId(),
                    'order_increment_id' => (string) $order->getIncrementId(),
                    'order_item_id' => (int) $item->getId(),
                ]);
            }
        }
    }
}
