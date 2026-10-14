<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * What the cards paid of an invoice counts as invoiced on the order.
 */
class InvoiceRegister implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $observer->getEvent()->getOrder();
        $base = (float) $invoice->getData('base_zero_giftcard_amount');
        if ($base > 0) {
            $order->setData('base_zero_giftcard_invoiced', (float) $order->getData('base_zero_giftcard_invoiced') + $base);
        }
    }
}
