<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Tests\Type\Fixtures;

use WP_UnitTestCase;

use Docalist\Type\Settings;

/**
 * @property string $url
 * @property int $timeout
 */
class MySettings extends Settings {
    protected static function loadSchema() {
        return [
            'fields' => [
                'a' => [ 'default' => 'default' ]
            ]
        ];
    }
}