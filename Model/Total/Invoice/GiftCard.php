<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * An invoice is paid by the order's gift cards first, as far as they reach
 * (what earlier invoices have not taken).
 */
class GiftCard extends AbstractTotal
{
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $orderBase = (float) $order->getData('base_zero_giftcard_amount');
        $leftBase = $orderBase - (float) $order->getData('base_zero_giftcard_invoiced');
        if ($leftBase <= 0.0001) {
            return $this;
        }
        $base = round(min($leftBase, (float) $invoice->getBaseGrandTotal()), 2);
        $amount = round((float) $order->getData('zero_giftcard_amount') * $base / $orderBase, 2);
        $invoice->setData('base_zero_giftcard_amount', $base)->setData('zero_giftcard_amount', $amount);
        $invoice->setBaseGrandTotal((float) $invoice->getBaseGrandTotal() - $base);
        $invoice->setGrandTotal(max(0.0, (float) $invoice->getGrandTotal() - $amount));

        return $this;
    }
}
