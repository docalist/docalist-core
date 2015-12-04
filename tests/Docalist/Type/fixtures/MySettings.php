<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Settings;
use Docalist\Type\Text;

/**
 * @property Text $a
 */
class MySettings extends Settings
{
    protected static function loadSchema()
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
