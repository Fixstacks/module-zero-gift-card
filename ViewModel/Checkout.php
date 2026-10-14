<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Zero\GiftCard\Model\Applied;
use Zero\GiftCard\Model\Codes;
use Zero\GiftCard\Model\Config;

/**
 * The checkout's gift card box: the cards on the cart and what each pays.
 */
class Checkout implements ArgumentInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly Config $config,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled((int) $this->checkoutSession->getQuote()->getStore()->getWebsiteId());
    }

    /**
     * @return array<int, array{code: string, masked: string, amount: string}>
     */
    public function applied(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $paying = [];
        foreach (Applied::used($quote) as $use) {
            $paying[$use['code']] = (float) $use['amount'];
        }
        $cards = [];
        foreach (Applied::codes($quote) as $code) {
            $cards[] = [
                'code' => $code,
                'masked' => Codes::mask($code),
                'amount' => $this->priceCurrency->format($paying[$code] ?? 0, false, PriceCurrencyInterface::DEFAULT_PRECISION, $quote->getStore()),
            ];
        }

        return $cards;
    }
}
