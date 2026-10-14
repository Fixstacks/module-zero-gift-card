<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Zero\GiftCard\Model\Config;
use Zero\GiftCard\Model\Purchase;

/**
 * Adding a gift card product to the cart: the chosen amount and recipient
 * become the line's option (lines differ by them), shown in the cart, the
 * order and its emails; the line costs the amount (in the cart's currency:
 * the request's custom price, which core strips from what shoppers post).
 */
class PrepareOptions implements ObserverInterface
{
    public function __construct(
        private readonly Json $json,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        $amounts = Config::amounts($product->getData(Config::AMOUNTS_ATTRIBUTE));
        if (!$amounts) {
            return;
        }
        $request = $observer->getEvent()->getBuyRequest();
        $card = Purchase::read($request->getData('giftcard'), $amounts);
        $product->addCustomOption(Purchase::OPTION, $this->json->serialize($card));
        $request->setData('custom_price', $this->priceCurrency->convertAndRound($card['amount']));
        $shown = [];
        if ($card['recipient_name'] !== '' || $card['recipient_email'] !== '') {
            // "Ann (ann@example.com)": the cart strips "<...>" as markup.
            $email = $card['recipient_email'];
            $shown[] = ['label' => (string) __('To'), 'value' => $card['recipient_name'] !== '' && $email !== '' ? $card['recipient_name'] . ' (' . $email . ')' : ($card['recipient_name'] . $email)];
        }
        if ($card['sender_name'] !== '') {
            $shown[] = ['label' => (string) __('From'), 'value' => $card['sender_name']];
        }
        if ($card['message'] !== '') {
            $shown[] = ['label' => (string) __('Message'), 'value' => $card['message']];
        }
        if ($shown) {
            $product->addCustomOption('additional_options', $this->json->serialize($shown));
        }
    }
}
