<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Tests\Type;

use WP_UnitTestCase;
use Docalist\Repository\Repository;
use Docalist\Repository\SettingsRepository;
use Docalist\Repository\DirectoryRepository;
use Docalist\Tests\Type\Fixtures\MySettings;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SettingsTest extends WP_UnitTestCase
{
    public function repositoryProvider()
    {
        delete_option('docalist-tests-type-fixtures-mysettings');

        $dir = sys_get_temp_dir() . '/DirectoryTest';
        $file = $dir . '/docalist-tests-type-fixtures-mysettings.json';
        file_exists($file) && unlink($file);

        return [
            [new SettingsRepository()],
            [new DirectoryRepository($dir)],
        ];
    }

    /**
     * @dataProvider repositoryProvider
     */
    public function testAll(Repository $repo)
    {
        // new quand les settings n'existent pas
        $s = new MySettings($repo);
        $this->assertSame('docalist-tests-type-fixtures-mysettings', $s->getID(), "L'id correspond au nom de la classe");
        $this->assertFalse($repo->has($s->getID()), 'Les paramètres ne figurent pas déjà dans le dépôt');
        $this->assertSame($s->getDefaultValue(), $s->getPhpValue(), 'Les paramètres ont leur valeur par défaut');
        $this->assertSame($repo, $s->repository(), 'Le dépôt retourné est celui pasé en paramètre');

        // delete quand les settings n'existe pas
        $s->delete();

        // save
        $s->a = 'value1';
        $s->save();
        $this->assertTrue($repo->has($s->getID()), 'Les paramètres ont été enregistrés dans le dépôt');

        $s = new MySettings($repo);
        $this->assertSame('value1', $s->a->getPhpValue(), 'Les paramètres ont été chargés depuis le dépôt');

        // reload
        $s->a = 'value2';
        $s->reload();
        $this->assertSame('value1', $s->a->getPhpValue(), 'Les paramètres ont été rechargés depuis le dépôt');

        // delete
        $s->delete();
        $this->assertFalse($repo->has($s->getID()), 'Les paramètres ont été supprimés dépôt');
        $this->assertSame('default', $s->a->getPhpValue(), 'Les paramètres ont repris leur valeur par défaut');

        // new en indiquant l'id
        $s = new MySettings($repo, 'myid');
        $this->assertSame('myid', $s->getID(), "L'ID peut être transmis en paramètre");
    }
}
