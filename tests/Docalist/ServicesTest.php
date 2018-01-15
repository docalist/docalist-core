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
use Docalist\Services;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ServicesTest extends WP_UnitTestCase
{
    public function testAll()
    {
        // Vide
        $services = new Services();
        $this->assertSame([], $services->getServices());
        $this->assertFalse($services->has('bool'));
        $this->assertFalse($services->isLoaded('bool'));

        // Services
        $o = (object)['name' => 'smith', 'firstname' => 'john', 'age' => 27];
        $called = false;
        $all = [
            'null' => null, // null peut être un service valide
            'int' => 1,
            'bool' => true,
            'string' => 'hello',
            'array' => [1, 'a', true],
            'object' => $o,
            2016 => 'deux-mille-seize', // un nom de service n'est pas obligatoirement une chaine
            'closure' => function() use (& $called, $o) {
                $called = true;

                return $o;
            }
        ];

        // Vérifie que tous les services simples existent et sont instanciés
        $services = new Services($all);
        $this->assertSame($all, $services->getServices());
        foreach(['null', 'int', 'bool', 'string', 'array', 'object', 2016] as $service) {
            $this->assertTrue($services->has($service));
            $this->assertTrue($services->isLoaded($service));
            $this->assertSame($all[$service], $services->get($service));
        }

        // Vérifie que la closure n'a pas été appellée
        $this->assertFalse($called);
        $this->assertTrue($services->has('closure'));
        $this->assertFalse($services->isLoaded('closure'));

        // Exécute la closute
        $this->assertSame($o, $services->get('closure'));

        // Vérifie que la closure a été appellée
        $this->assertTrue($called);
        $this->assertTrue($services->has('closure'));
        $this->assertTrue($services->isLoaded('closure'));

        // Vérifie qu'elle n'est pas appellée une seconde fois
        $called = false;
        $this->assertSame($o, $services->get('closure'));
        $this->assertFalse($called);

        // Teste add()
        $this->assertSame($services, $services->add('other', 'service')); // fluent, retourne self
        $this->assertTrue($services->has('other'));
        $this->assertTrue($services->isLoaded('other'));
        $this->assertSame('service', $services->get('other'));
    }

    /**
     * Teste add() avec un service qui existe déjà.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage already registered
     */
    public function testAddExistant()
    {
        $services = new Services(['aaa' => 'Hi!']);

        $services->add('aaa', 'Oups!');
    }

    /**
     * Teste get() avec un service qui n'existe pas.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage not found
     */
    public function testNotFound()
    {
        $services = new Services(['aaa' => 'Hi!']);

        $services->get('bbb');
    }
}
