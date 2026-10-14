<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model;

use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Emails a card to its recipient (or to whoever bought it, when no recipient
 * email was given): the code, the amount, the message, where to spend it.
 */
class Mailer
{
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly Cards $cards,
        private readonly LoggerInterface $logger
    ) {
    }

    public function send(array $card, ?string $fallbackEmail = null, ?string $fallbackName = null): bool
    {
        $email = (string) ($card['recipient_email'] ?: $fallbackEmail);
        if ($email === '') {
            return false;
        }
        $store = $this->storeManager->getStore((int) ($card['store_id'] ?? 0) ?: null);
        $storeId = (int) $store->getId();
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->template($storeId))
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars([
                    'code' => (string) $card['code'],
                    'amount' => $store->getBaseCurrency()->formatPrecision((float) $card['initial_amount'], 2, [], false),
                    'recipient_name' => (string) ($card['recipient_name'] ?: $fallbackName),
                    'sender_name' => (string) $card['sender_name'],
                    'message' => (string) $card['message'],
                    'store_url' => $store->getBaseUrl(),
                    'balance_url' => $store->getUrl('giftcard/balance', ['_query' => ['code' => $card['code']]]),
                    'store_name' => (string) $store->getFrontendName(),
                    'expires' => $card['expires_at'] ? substr((string) $card['expires_at'], 0, 10) : '',
                ])
                ->setFromByScope($this->config->identity($storeId), $storeId)
                ->addTo($email, (string) ($card['recipient_name'] ?: $fallbackName))
                ->getTransport();
            $transport->sendMessage();
            $this->cards->markSent((int) $card['card_id']);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Zero_GiftCard: card %d not emailed: %s', (int) $card['card_id'], $e->getMessage()));

            return false;
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
