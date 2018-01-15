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

use Docalist\Type\Settings;
use Docalist\Type\Text;

/**
 * @property Text $a
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MySettings extends Settings
{
    public static function loadSchema()
    {
        return [
            'fields' => [
                'a' => [
                    'type' => 'Docalist\Type\Text',
                    'default' => 'default',
                ],
            ],
        ];
    }
}
