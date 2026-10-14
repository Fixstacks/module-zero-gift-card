<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Zero\GiftCard\Model\Applied;
use Zero\GiftCard\Model\Cards;
use Zero\GiftCard\Model\Codes;

/**
 * The checkout's gift card box (HTMX POST; form key checked by core's CSRF
 * validation): adds the card in `giftcard_code` (if it can pay), or takes
 * off the one in `remove`; collects the totals; answers with the box and
 * the totals (out of band), as the discount code does.
 */
class Apply implements HttpPostActionInterface
{
    private const MAX_CARDS = 5;

    public function __construct(
        private readonly LayoutFactory $resultLayoutFactory,
        private readonly CheckoutSession $checkoutSession,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly Cards $cards,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @return Layout
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $error = null;
        $typed = trim((string) $this->request->getParam('giftcard_code'));
        if ($quote->getId()) {
            $codes = Applied::codes($quote);
            $remove = (string) $this->request->getParam('remove');
            if ($remove !== '') {
                $codes = array_values(array_diff($codes, [$remove]));
                $typed = '';
            } elseif ($typed === '') {
                $error = (string) __('Enter a gift card code.');
            } else {
                $card = $this->cards->byCode($typed);
                $error = $this->cards->problem($card, (int) $quote->getStore()->getWebsiteId());
                if ($error === null && in_array($card['code'], $codes, true)) {
                    $error = (string) __('This gift card is already on the order.');
                } elseif ($error === null && count($codes) >= self::MAX_CARDS) {
                    $error = (string) __('An order takes up to %1 gift cards.', self::MAX_CARDS);
                } elseif ($error === null) {
                    $codes[] = (string) $card['code'];
                    $typed = '';
                }
            }
            Applied::setCodes($quote, $codes);
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $this->cartRepository->save($quote);
        }
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();
        $layout = $resultLayout->getLayout();
        $box = $layout->getBlock('zero.giftcard.checkout');
        if ($box) {
            $box->setData('error', $error)->setData('typed', $error !== null ? Codes::normalize($typed) : '');
        }
        $layout->addOutputElement('zero.giftcard.checkout');
        $layout->addOutputElement('zero.checkout.ordersummarytotals');

        return $resultLayout;
    }
}
