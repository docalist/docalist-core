<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests;

use WP_UnitTestCase;
use Docalist\Views;
use InvalidArgumentException;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ViewsTest extends WP_UnitTestCase
{
    public function testConstructGetGroupsSetGroups()
    {
        $views = new Views();
        $this->assertSame([], $views->getGroups());

        $ds = DIRECTORY_SEPARATOR;

        $views = new Views(['' => '/a/', 'test' => ['/b/', '/c/']]);
        $this->assertSame(
            [
                '' => "{$ds}a{$ds}",
                'test' => ["{$ds}c{$ds}", "{$ds}b{$ds}"] // Ordre inverse
            ],
            $views->getGroups()
        );

        $views->setGroups(['' => '/z/']);
        $this->assertSame(
            ['' => "{$ds}z{$ds}"],
            $views->getGroups()
        );

        $views->setGroups([]);
        $this->assertSame([], $views->getGroups());
    }

    /**
     * Vérifie que les paramètres sont correctement transmis à la vue.
     *
     * On utilise une vue spéciale (vars) qui n'affiche rien mais qui retourne les variables auxquelles
     * elle a accès.
     */
    public function testParameters()
    {
        $views = new Views(['' => __DIR__ . '/views/']);

        $ds = DIRECTORY_SEPARATOR;

        $tests = [
            [], // aucun paramètre
            ['hello' => 'world'],
            ['firstname' => 'john', 'surname' => 'smith'],
            ['nb' => 2, 'values' => [1, 2, 3]],
            ['nb' => 2, 'values' => [1, 2, 3], /*'this' => $this*/],
        ];

        foreach ($tests as $data) {
            $vars = $views->display('vars', $data);
            $this->assertSame([
                'view' => [
                    'name' => 'vars',
                    'path' => __DIR__ . "{$ds}views{$ds}vars.php",
                    'data' => $data
                ],
            ] + $data, $vars);
        }
    }

    /**
     * Vérifie que la vue peut appeller toutes les méthodes de l'objet '$this' (y compris les private).
     */
    public function testThis()
    {
        $views = new Views(['' => __DIR__ . '/views/']);
        $this->assertSame(
            ['public', 'protected', 'private'],
            $views->display('this', ['this' => $this])
        );
    }

    private function privateMethod()
    {
        return 'private';
    }

    protected function protectedMethod()
    {
        return 'protected';
    }

    public function publicMethod()
    {
        return 'public';
    }

    public function testGetPath()
    {
        // dir1 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir1/', __DIR__ . '/views/dir2/'],
        ]);

        $ds = DIRECTORY_SEPARATOR;

        $this->assertSame(__DIR__ . "{$ds}views{$ds}vars.php", $views->getPath('vars'));
        $this->assertSame(__DIR__ . "{$ds}views{$ds}dir2{$ds}view.php", $views->getPath('dir:view'));
        $this->assertFalse($views->getPath('toto'));
        $this->assertFalse($views->getPath('dir:toto'));

        // dir2 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir2/', __DIR__ . '/views/dir1/'],
        ]);

        $this->assertSame(__DIR__ . "{$ds}views{$ds}vars.php", $views->getPath('vars'));
        $this->assertSame(__DIR__ . "{$ds}views{$ds}dir1{$ds}view.php", $views->getPath('dir:view'));
    }

    public function testDisplay()
    {
        // dir1 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir1/', __DIR__ . '/views/dir2/'],
        ]);

        ob_start();
        $return = $views->display('dir:view');
        $result = ob_get_clean();

        $this->assertSame('dir2', $result);
        $this->assertSame(2, $return);

        // dir2 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir2/', __DIR__ . '/views/dir1/'],
        ]);

        ob_start();
        $return = $views->display('dir:view');
        $result = ob_get_clean();

        $this->assertSame('dir1', $result);
        $this->assertSame(1, $return);
    }

    public function testRender()
    {
        // dir1 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir1/', __DIR__ . '/views/dir2/'],
        ]);

        $this->assertSame('dir2', $views->render('dir:view'));

        // dir2 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir2/', __DIR__ . '/views/dir1/'],
        ]);

        $this->assertSame('dir1', $views->render('dir:view'));
    }

    /**
     * Teste getPath() avec une vue sans group quand le groupe '' n'existe pas.
     */
    public function testDefaultGroupNotFound()
    {
        $views = new Views();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown group');

        $views->getPath('toto');
    }

    /**
     * Teste getPath() avec un groupe qui n'existe pas.
     */
    public function testNoGroupNotFound()
    {
        $views = new Views();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown group');

        $views->getPath('docalist-core:toto');
    }

    /**
     * Teste display() avec une vue qui n'existe pas.
     */
    public function testViewNotFound1()
    {
        $views = new Views(['' => __DIR__ . '/views/']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('View not found');

        $views->display('toto');
    }

    /**
     * Teste display() avec une vue qui n'existe pas.
     */
    public function testViewNotFound2()
    {
        $views = new Views(['dir1' => __DIR__ . '/views/dir1/']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('View not found');

        $views->display('dir1:toto');
    }
}
