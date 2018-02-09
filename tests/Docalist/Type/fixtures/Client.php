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

use Docalist\Type\Entity;
use Docalist\Type\Text;
use Docalist\Tests\Type\Fixtures\Facture;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Client extends Entity
{
    public static function loadSchema()
    {
        return [
            'fields' => [
                'name' => [
                    'type' => Text::class,
                    'default' => 'noname'
                ],
                'factures' => [
                    'type' => Facture::class,
                    'repeatable' => true,
                    'key' => 'code'
                ]
            ]
        ];
    }
}
