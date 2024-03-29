<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Composite;
use Docalist\Type\Decimal;
use Docalist\Type\Text;

/**
 * @property Decimal $amount
 * @property Text    $currency
 *
 * @method float  amount(float $amount = null)
 * @method string currency(string $currency = null)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Money extends Composite
{
    public static function loadSchema(): array
    {
        return [
            'label'  => 'prix',
            'fields' => [
                'amount'   => [
                    'type'    => Decimal::class,
                    'default' => 0,
                ],
                'currency' => [
                    'type'    => Text::class,
                    'default' => 'EUR',
                ],
            ],
        ];
    }
}
