<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\EmailAddress;

/**
 * What a shopper asks for when buying a card (the add-to-cart form's
 * giftcard[...] fields), checked: an amount the product sells, a valid
 * recipient email if any, short names and message.
 */
class Purchase
{
    public const OPTION = 'zero_giftcard';

    /**
     * @param float[] $amounts The product's
     * @return array{amount: float, recipient_name: string, recipient_email: string, sender_name: string, message: string}
     * @throws LocalizedException
     */
    public static function read(mixed $input, array $amounts): array
    {
        $input = is_array($input) ? $input : [];
        $amount = round((float) ($input['amount'] ?? 0), 2);
        if (!in_array($amount, $amounts, true)) {
            throw new LocalizedException(__('Choose the gift card\'s amount.'));
        }
        $email = trim((string) ($input['recipient_email'] ?? ''));
        if ($email !== '' && !(new EmailAddress())->isValid($email)) {
            throw new LocalizedException(__('Enter a valid email for the recipient, or leave it empty to get the card yourself.'));
        }

        return [
            'amount' => $amount,
            'recipient_name' => mb_substr(trim((string) ($input['recipient_name'] ?? '')), 0, 100),
            'recipient_email' => $email,
            'sender_name' => mb_substr(trim((string) ($input['sender_name'] ?? '')), 0, 100),
            'message' => mb_substr(trim((string) ($input['message'] ?? '')), 0, 500),
        ];
    }
}
