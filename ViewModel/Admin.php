<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\ViewModel;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zero\GiftCard\Model\Cards;

/**
 * Marketing > Gift Cards.
 */
class Admin implements ArgumentInterface
{
    public function __construct(
        private readonly Cards $cards,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url
    ) {
    }

    public function search(): string
    {
        return trim((string) $this->request->getParam('q'));
    }

    public function all(): array
    {
        return $this->cards->all($this->search());
    }

    public function card(): ?array
    {
        $id = (int) $this->request->getParam('id');

        return $id ? $this->cards->get($id) : null;
    }

    public function history(int $cardId): array
    {
        return $this->cards->history($cardId);
    }

    public function money(array $card, float $amount): string
    {
        $website = $this->storeManager->getWebsite((int) $card['website_id']);

        return $website->getBaseCurrency()->formatPrecision($amount, 2, [], false);
    }

    /**
     * @return array<int, string> Store view id => "Website / Store view"
     */
    public function storeViews(): array
    {
        $views = [];
        foreach ($this->storeManager->getStores() as $store) {
            $views[(int) $store->getId()] = $store->getWebsite()->getName() . ' / ' . $store->getName();
        }

        return $views;
    }

    public function url(string $route, array $params = []): string
    {
        return $this->url->getUrl($route, $params);
    }
}
