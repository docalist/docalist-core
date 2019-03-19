<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Composite;
use Docalist\Type\Decimal;
use Docalist\Type\Text;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Money extends Composite
{
    public static function loadSchema(): array
    {
        return [
            'label' => 'prix',
            'fields' => [
                'amount' => [
                    'type' => Decimal::class,
                    'default' => 0
                ],
                'currency' => [
                    'type' => Text::class,
                    'default' => 'EUR'
                ],
            ]
        ];
    }
}
