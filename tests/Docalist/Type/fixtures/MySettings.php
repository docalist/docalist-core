<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
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
    public static function loadSchema(): array
    {
        return [
            'fields' => [
                'a' => [
                    'type' => Text::class,
                    'default' => 'default',
                ],
            ],
        ];
    }
}
