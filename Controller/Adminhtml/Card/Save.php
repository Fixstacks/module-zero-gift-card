<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Controller\Adminhtml\Card;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\EmailAddress;
use Magento\Store\Model\StoreManagerInterface;
use Zero\GiftCard\Model\Cards;
use Zero\GiftCard\Model\Mailer;

/**
 * Makes a card by hand (a prize, a goodwill gesture), on a store view's
 * website; emailed to the recipient if asked. Or, for a card (id): turns it
 * on or off, emails it again, or changes its balance.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_GiftCard::cards';

    public function __construct(
        Context $context,
        private readonly Cards $cards,
        private readonly Mailer $mailer,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();
        $id = (int) $request->getParam('id');
        try {
            if ($id) {
                $card = $this->cards->get($id) ?? throw new LocalizedException(__('The gift card no longer exists.'));
                match ((string) $request->getParam('do')) {
                    'toggle' => $this->cards->setActive($id, !(int) $card['is_active']),
                    'send' => $this->mailer->send($card) ?: throw new LocalizedException(__('The card has no recipient email, or the email failed (see the log).')),
                    'adjust' => $this->cards->change($id, round((float) $request->getParam('amount'), 2), Cards::ADMIN, null, trim((string) $request->getParam('comment')) ?: null),
                    default => throw new LocalizedException(__('Nothing to do.')),
                };
                $this->messageManager->addSuccessMessage(__('The gift card is updated.'));

                return $this->resultRedirectFactory->create()->setPath('zero_giftcard/card/index', ['id' => $id]);
            }
            $amount = round((float) $request->getParam('amount'), 2);
            if ($amount <= 0) {
                throw new LocalizedException(__('Enter the card\'s amount.'));
            }
            $email = trim((string) $request->getParam('recipient_email'));
            if ($email !== '' && !(new EmailAddress())->isValid($email)) {
                throw new LocalizedException(__('Enter a valid recipient email.'));
            }
            $store = $this->storeManager->getStore((int) $request->getParam('store_id'));
            $card = $this->cards->create($amount, (int) $store->getWebsiteId(), [
                'recipient_name' => mb_substr(trim((string) $request->getParam('recipient_name')), 0, 100),
                'recipient_email' => $email,
                'sender_name' => mb_substr(trim((string) $request->getParam('sender_name')), 0, 100),
                'message' => mb_substr(trim((string) $request->getParam('message')), 0, 500),
                'store_id' => (int) $store->getId(),
            ], (string) __('Made by hand'));
            if ($email !== '' && $request->getParam('send')) {
                $this->mailer->send($card);
            }
            $this->messageManager->addSuccessMessage(__('Gift card %1 is made.', $card['code']));

            return $this->resultRedirectFactory->create()->setPath('zero_giftcard/card/index', ['id' => $card['card_id']]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('zero_giftcard/card/index', $id ? ['id' => $id] : []);
    }
}
