<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Zero\GiftCard\Model\Codes;
use Zero\GiftCard\Model\Config;
use Zero\GiftCard\Model\Purchase;

class CodesTest extends TestCase
{
    public function testGeneratedCodesAreFourGroupsWithoutLookalikes(): void
    {
        $code = Codes::generate();
        $this->assertMatchesRegularExpression('/^[A-HJKMNP-Z2-9]{4}(-[A-HJKMNP-Z2-9]{4}){3}$/', $code);
        $this->assertNotSame($code, Codes::generate());
    }

    public function testTypedCodesAreReadLeniently(): void
    {
        $this->assertSame('ABCD-EFGH-JKMN-PQRS', Codes::normalize(' abcd efgh-jkmn pqrs '));
        $this->assertSame('ABC', Codes::normalize('a-b-c'));
        $this->assertSame('••••-PQRS', Codes::mask('ABCD-EFGH-JKMN-PQRS'));
    }

    public function testAmountsParsedSorted(): void
    {
        $this->assertSame([25.0, 50.0, 100.0], Config::amounts('100, 25;50 50 x -5'));
        $this->assertSame([], Config::amounts(null));
    }

    public function testPurchaseNeedsAnAmountTheProductSells(): void
    {
        $card = Purchase::read(['amount' => '50', 'recipient_email' => '', 'message' => str_repeat('a', 600)], [25.0, 50.0]);
        $this->assertSame(50.0, $card['amount']);
        $this->assertSame(500, mb_strlen($card['message']));
        $this->expectException(LocalizedException::class);
        Purchase::read(['amount' => '30'], [25.0, 50.0]);
    }

    public function testPurchaseRejectsABadRecipientEmail(): void
    {
        $this->expectException(LocalizedException::class);
        Purchase::read(['amount' => '25', 'recipient_email' => 'not an email'], [25.0]);
    }
}
