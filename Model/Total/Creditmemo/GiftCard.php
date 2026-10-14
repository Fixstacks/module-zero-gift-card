<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * A refund goes back to the gift cards first, up to what they paid of the
 * invoices and earlier refunds have not given back; the rest to the payment.
 * A refund all on cards has nothing left for the payment: allowed.
 */
class GiftCard extends AbstractTotal
{
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $orderBase = (float) $order->getData('base_zero_giftcard_amount');
        $leftBase = (float) $order->getData('base_zero_giftcard_invoiced') - (float) $order->getData('base_zero_giftcard_refunded');
        if ($leftBase <= 0.0001 || $orderBase <= 0) {
            return $this;
        }
        $base = round(min($leftBase, (float) $creditmemo->getBaseGrandTotal()), 2);
        $amount = round((float) $order->getData('zero_giftcard_amount') * $base / $orderBase, 2);
        $creditmemo->setData('base_zero_giftcard_amount', $base)->setData('zero_giftcard_amount', $amount);
        $creditmemo->setBaseGrandTotal((float) $creditmemo->getBaseGrandTotal() - $base);
        $creditmemo->setGrandTotal(max(0.0, (float) $creditmemo->getGrandTotal() - $amount));
        if ($creditmemo->getBaseGrandTotal() <= 0.0001) {
            $creditmemo->setAllowZeroGrandTotal(true);
        }

        return $this;
    }
}
