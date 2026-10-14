<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Controller\Balance;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Zero\GiftCard\Model\Config;

/**
 * /giftcard/balance: what is left on a card (?code=...). Not cached.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config
    ) {
    }

    public function execute()
    {
        if (!$this->config->isEnabled((int) $this->storeManager->getStore()->getWebsiteId())) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Gift Card Balance'));
        $page->getConfig()->setRobots('NOINDEX,NOFOLLOW');

        return $page;
    }
}
