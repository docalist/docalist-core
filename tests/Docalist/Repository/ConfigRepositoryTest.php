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

namespace Docalist\Tests\Repository;

use WP_UnitTestCase;

use Docalist\Repository\ConfigRepository;
use Docalist\Type\Settings;

/**
 * @property string $url
 * @property int $timeout
 */
class MySettings extends Settings {
    protected static function loadSchema() {
        return [
            'fields' => [
                'url' => [ 'default' => 'http://127.0.0.1:9200/' ],
                'timeout' => [ 'type' => 'int', 'default' => 30 ],
            ]
        ];
    }
}

class ConfigRepositoryTest extends WP_UnitTestCase {
    public function testNew() {
        $repo = new ConfigRepository();

        $dir = docalist('config-dir');
        $this->assertTrue(is_dir($dir));

        $repo->save(new MySettings);

        $this->assertFileExists($dir . '/docalist-tests-repository-mysettings.json');
    }
}