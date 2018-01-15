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
use Docalist\Autoloader;
use InvalidArgumentException;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AutoloaderTest extends WP_UnitTestCase
{
    /**
     * Vérifie que l'autoloader est correctement enregistré/désinstallé lors de la création/suppression de l'objet.
     */
    public function testConstructDestruct()
    {
        $before = spl_autoload_functions();
        new Autoloader();
        $after = spl_autoload_functions();
        $this->assertSame(1, count($after) - count($before)); // Un et un seul appel à spl_autoload() a été fait
    }

    public function testGetNamespaces()
    {
        $autoloader = new Autoloader();
        $this->assertSame([], $autoloader->getNamespaces());

        $autoloader = new Autoloader([]);
        $this->assertSame([], $autoloader->getNamespaces());

        $autoloader = new Autoloader(['test' => 'test']);
        $this->assertSame(['test' => 'test'], $autoloader->getNamespaces());

        $autoloader = new Autoloader(['a' => 'b', 'c' => 'd']);
        $this->assertSame(['a' => 'b', 'c' => 'd'], $autoloader->getNamespaces());
    }

    public function testAdd()
    {
        $autoloader = new Autoloader();
        $autoloader->add('test', 'test');
        $this->assertSame(['test' => 'test'], $autoloader->getNamespaces());

        $autoloader->add('a', 'b');
        $this->assertSame(['test' => 'test', 'a' => 'b'], $autoloader->getNamespaces());

        $autoloader->add('c', 'd');
        $this->assertSame(['test' => 'test', 'a' => 'b', 'c' => 'd'], $autoloader->getNamespaces());

        $autoloader->add('c', 'd'); // déjà enregistré mais même path, pas d'erreur
    }


    /**
     * Teste add() avec un namespace déjà enregistré (et différent)
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage already registered with a different path
     */
    public function testAddConflict()
    {
        $autoloader = new Autoloader();
        $autoloader->add('test', 'test');

        $autoloader->add('test', 'test2');
    }

    public function testResolve()
    {
        $ds = DIRECTORY_SEPARATOR;
        $autoloader = new Autoloader([
            'a' => '/src/a',
            'a\b' => '/lib/a/b',
            'aa' => '/other',
        ]);

        $this->assertFalse($autoloader->resolve('')); // pas un nom de classe, retourne false
        $this->assertFalse($autoloader->resolve('z')); // pas de namespace, retourne false
        $this->assertFalse($autoloader->resolve('z\a')); // no match, retourne false

        $this->assertSame("/src/a{$ds}class.php", $autoloader->resolve('a\class'));
        $this->assertSame("/lib/a/b{$ds}class.php", $autoloader->resolve('a\b\class')); // ça matche le plus long
        $this->assertSame("/src/a{$ds}z{$ds}class.php", $autoloader->resolve('a\z\class'));
        $this->assertSame("/other{$ds}class.php", $autoloader->resolve('aa\class')); // aa, pas a
    }

    public function testAutoload()
    {
        $autoloader = new Autoloader([
            'Test\Autoloader' => __DIR__ . '/Autoloader',
        ]);

        $class = 'Test\Autoloader\TestClass';
        $this->assertFalse(class_exists($class, false));
        $this->assertTrue($autoloader->autoload($class));
        $this->assertTrue(class_exists($class, false));

        $class = 'Non\Existent\TestClass';
        $this->assertFalse(class_exists($class, false));
        $this->assertFalse($autoloader->autoload($class));
        $this->assertFalse(class_exists($class, false));
    }
}
