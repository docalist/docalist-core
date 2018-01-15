<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Tests\Repository;

use WP_UnitTestCase;

use Docalist\Repository\ConfigRepository;
use Docalist\Type\Settings;

/**
 * @property string $url
 * @property int $timeout
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MySettings extends Settings {
    static public function loadSchema() {
        return [
            'fields' => [
                'url' => [ 'type' => 'Docalist\Type\Text', 'default' => 'http://127.0.0.1:9200/' ],
                'timeout' => [ 'type' => 'Docalist\Type\Integer', 'default' => 30 ],
            ]
        ];
    }
}

class ConfigRepositoryTest extends WP_UnitTestCase {
    public function testNew() {
        $repo = new ConfigRepository();

        $dir = docalist('config-dir');
        $this->assertTrue(is_dir($dir));

        $settings = new MySettings($repo);
        $repo->save($settings);

        $this->assertFileExists($dir . '/docalist-tests-repository-mysettings.json');
    }
}