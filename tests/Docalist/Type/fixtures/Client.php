<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Entity;

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
                    'type' => 'Docalist\Type\Text',
                    'default' => 'noname'
                ],
                'factures' => [
                    'type' => 'Docalist\Tests\Type\Fixtures\Facture*',
                    'key' => 'code'
                ]
            ]
        ];
    }
}
