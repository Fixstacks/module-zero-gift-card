<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Controller\Adminhtml\Card;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Marketing > Gift Cards: every card (or one, with its history: ?id=).
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_GiftCard::cards';

    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Zero_GiftCard::cards');
        $page->getConfig()->getTitle()->prepend(__('Gift Cards'));

        return $page;
    }
}
