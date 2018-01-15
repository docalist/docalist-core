<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
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

        $groups = ['' => '/a/', 'test' => ['/b/', '/c/']];
        $views = new Views($groups);
        $this->assertSame($groups, $views->getGroups());

        $groups2 = ['' => '/z/'];
        $views->setGroups($groups2);
        $this->assertSame($groups2, $views->getGroups());

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

        $tests = [
            [], // aucun paramètre
            ['hello' => 'world'],
            ['firstname' => 'john', 'surname' => 'smith'],
            ['nb' => 2, 'values' => [1, 2, 3]],
            ['nb' => 2, 'values' => [1, 2, 3], 'this' => $this],
        ];

        foreach ($tests as $data) {
            $vars = $views->display('vars', $data);
            $this->assertSame([
                'view' => [
                    'name' => 'vars',
                    'path' => __DIR__ . '/views/vars.php',
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

        $this->assertSame(__DIR__ . '/views/vars.php', $views->getPath('vars'));
        $this->assertSame(__DIR__ . '/views/dir1/view.php', $views->getPath('dir:view'));
        $this->assertFalse($views->getPath('toto'));
        $this->assertFalse($views->getPath('dir:toto'));

        // dir2 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir2/', __DIR__ . '/views/dir1/'],
        ]);

        $this->assertSame(__DIR__ . '/views/vars.php', $views->getPath('vars'));
        $this->assertSame(__DIR__ . '/views/dir2/view.php', $views->getPath('dir:view'));
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

        $this->assertSame('dir1', $result);
        $this->assertSame(1, $return);

        // dir2 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir2/', __DIR__ . '/views/dir1/'],
        ]);

        ob_start();
        $return = $views->display('dir:view');
        $result = ob_get_clean();

        $this->assertSame('dir2', $result);
        $this->assertSame(2, $return);
    }

    public function testRender()
    {
        // dir1 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir1/', __DIR__ . '/views/dir2/'],
        ]);

        $this->assertSame('dir1', $views->render('dir:view'));

        // dir2 prioritaire
        $views = new Views([
            '' => __DIR__ . '/views/',
            'dir' => [__DIR__ . '/views/dir2/', __DIR__ . '/views/dir1/'],
        ]);

        $this->assertSame('dir2', $views->render('dir:view'));
    }

    /**
     * Teste getPath() avec une vue sans group quand le groupe '' n'existe pas.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid group
     */
    public function testDefaultGroupNotFound()
    {
        $views = new Views();
        $views->getPath('toto');
    }

    /**
     * Teste getPath() avec un groupe qui n'existe pas.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid group
     */
    public function testNoGroupNotFound()
    {
        $views = new Views();
        $views->getPath('docalist-core:toto');
    }

    /**
     * Teste display() avec une vue qui n'existe pas.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage View not found
     */
    public function testViewNotFound1()
    {
        $views = new Views(['' => __DIR__ . '/views/']);
        $views->display('toto');
    }

    /**
     * Teste display() avec une vue qui n'existe pas.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage View not found
     */
    public function testViewNotFound2()
    {
        $views = new Views(['dir1' => __DIR__ . '/views/dir1/']);
        $views->display('dir1:toto');
    }
}
