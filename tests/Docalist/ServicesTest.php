<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Tests;

use Docalist\Test\DocalistTestCase;
use Exception;
use InvalidArgumentException;
use LogicException;
use stdClass;
use WP_UnitTestCase;
use Docalist\Services;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ServicesTest extends DocalistTestCase
{
    // ALIASES
    public function testAlias(): void
    {
        $services = new Services();

        $service = new stdClass();
        $services->set('service', $service);

        $services->alias('alias', 'service');
        $this->assertSame($service, $services->get('alias'));
    }

    public function testAliasCascade(): void
    {
        $services = new Services();

        $service = new stdClass();

        $services->alias('alias3', 'alias2');
        $services->alias('alias2', 'alias1');
        $services->alias('alias1', 'service');
        $services->set('service', $service);
        $this->assertSame($service, $services->get('alias3'));
    }

    public function testAliasOfServiceDefinedLater(): void
    {
        $services = new Services();

        $services->alias('alias', 'service');

        $service = new stdClass();
        $services->set('service', $service);

        $this->assertSame($service, $services->get('alias'));
    }

    public function testAliasOfInexistantService(): void
    {
        $services = new Services();

        $services->alias('alias', 'service');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "service" not found');

        $services->get('alias');
    }

    public function testAliasAlreadyDefined(): void
    {
        $services = new Services();

        $services->alias('alias', 'service');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias "alias" already defined ("service")');

        $services->alias('alias', 'service');
    }

    // DEPRECATED
    public function testDeprecated(): void
    {
        $services = new Services();

        $service = new stdClass();
        $services->set('newService', $service);

        $services->deprecate('oldName', 'newService', '2023-11-27');

        $deprecations = [];
        set_error_handler(static function ($errno, $errstr) use(&$deprecations) {
            $deprecations[] = $errstr;
        });

        $services->get('oldName');

        $this->assertSame([
            'Service "oldName" is deprecated since 2023-11-27, use "newService" instead.',
        ], $deprecations);
    }

    public function testDeprecatedCascade(): void
    {
        $services = new Services();

        $service = new stdClass();

        $services->deprecate('a', 'b', '2023-11-27');
        $services->deprecate('b', 'c', '2023-11-27');
        $services->deprecate('c', 'd', '2023-11-27');

        $services->set('d', $service);

        $deprecations = [];
        set_error_handler(static function ($errno, $errstr) use(&$deprecations) {
            $deprecations[] = $errstr;
        });

        $services->get('a');

        $this->assertSame([
            'Service "a" is deprecated since 2023-11-27, use "b" instead.',
            'Service "b" is deprecated since 2023-11-27, use "c" instead.',
            'Service "c" is deprecated since 2023-11-27, use "d" instead.',
        ], $deprecations);
    }
    public function testDeprecatedTwice(): void
    {
        $services = new Services();

        $services->deprecate('a', 'b', '2023-11-27');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "a" is already deprecated ("b", 2023-11-27)');
        $services->deprecate('a', 'b', '2023-11-27');
    }

    public function testHas(): void
    {
        $services = new Services();
        $this->assertFalse($services->has('service'));

        $services->set('service', new stdClass());
        $this->assertTrue($services->has('service'));
    }

    public function testGet(): void
    {
        $services = new Services();

        $id = 'service';
        $service = new stdClass();
        $services->set($id, $service);

        // Vérifie que le service existe mais qu'il n'a pas été chargé
        $this->assertTrue($services->has($id));
        $this->assertFalse($services->isLoaded($id));

        // Récupère le service, c'est à ce moment qu'il est initializé
        $this->assertSame($service, $services->get($id));
        $this->assertTrue($services->has($id));
        $this->assertTrue($services->isLoaded('service'));

        // Ensuite il est retourné directement
        $this->assertSame($service, $services->get('service'));
        $this->assertTrue($services->has($id));
        $this->assertTrue($services->isLoaded('service'));
    }

    public function testIsLoaded(): void
    {
        $services = new Services();

        $services->alias('alias3', 'alias2');
        $services->alias('alias2', 'alias1');
        $services->alias('alias1', 'service');
        $services->set('service', new stdClass());

        $this->assertFalse($services->isLoaded('service'));
        $this->assertFalse($services->isLoaded('alias1'));
        $this->assertFalse($services->isLoaded('alias2'));
        $this->assertFalse($services->isLoaded('alias3'));

        $services->get('service');

        $this->assertTrue($services->isLoaded('service'));
        $this->assertTrue($services->isLoaded('alias1'));
        $this->assertTrue($services->isLoaded('alias2'));
        $this->assertTrue($services->isLoaded('alias3'));
    }

    public function testSet(): void
    {
        $services = new Services();

        $services->set('service', new stdClass());
        $this->assertTrue($services->has('service'));
    }

    public function testSetDuplicate(): void
    {
        $services = new Services();

        $services->set('service', new stdClass());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "service" already exists');
        $services->set('service', new stdClass());
    }

    public function testSetParameterDuplicate(): void
    {
        $services = new Services();
        $services->setParameter('service', 'inclus');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A parameter named "service" already exists');
        $services->set('service', new stdClass());
    }

    public function testInitializer(): void
    {
        $services = new Services();

        $id = 'service';
        $service = new stdClass();
        $calls = 0;
        $services->set($id, function(mixed $arg1, mixed $arg2) use ($id, $services, &$calls, $service) {
            // Vérifie que l'initializer a été appelé avec les bons paramètres
            $this->assertSame($services, $arg1);
            $this->assertSame($id, $arg2);
            $this->assertSame(2, func_num_args());

            // pendant l'initialisation, le service existe mais n'est pas encore chargé
            $this->assertTrue($services->has($id));
            $this->assertFalse($services->isLoaded($id));

            // Compte le nombre d'appels
            ++$calls;

            // Retourne le service
            return $service;
        });

        // Vérifie que le service existe mais qu'il n'a pas été chargé
        $this->assertTrue($services->has($id));
        $this->assertFalse($services->isLoaded($id));
        $this->assertSame(0, $calls);

        // Récupère le service, c'est à ce moment qu'il est initializé
        $this->assertSame($service, $services->get($id));
        $this->assertSame(1, $calls);
        $this->assertTrue($services->has($id));
        $this->assertTrue($services->isLoaded('service'));

        // Ensuite il est retourné directement et l'initializer n'est jamais rappellé
        $this->assertSame($service, $services->get('service'));
        $this->assertSame(1, $calls);
    }

    public function testBadInitializer(): void
    {
        $services = new Services();

        $services->set('service', fn() => 'Hello !"');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Initializer of service "service" must return an object');

        $services->get('service');
    }

    public function testListener(): void
    {
        $services = new Services();

        $id = 'service';
        $service = new stdClass();
        $services->set($id, $service);
        $calls = ['first' => 0, 'second' => 0, 'all' => 0];

        // Premier listener
        $services->listen('service', function($arg1, $arg2, $arg3) use ($services, $service, $id, &$calls) {
            // Vérifie que le listener a été appelé avec les bons paramètres
            $this->assertSame($service, $arg1);
            $this->assertSame($services, $arg2);
            $this->assertSame($id, $arg3);
            $this->assertSame(3, func_num_args());

            // vérifie que le listener est appellé après l'initialisation
            $this->assertTrue($services->isLoaded($id));

            // Compte le nombre d'appels et vérifie l'ordre d'appel
            ++$calls['first'];
            $this->assertSame(['first' => 1, 'second' => 0, 'all' => 0], $calls);
        });

        // Second listener
        $services->listen('service', function($arg1, $arg2, $arg3) use (&$calls) {
            ++$calls['second'];
            $this->assertSame(['first' => 1, 'second' => 1, 'all' => 0], $calls);
        });

        // Listener "all"
        $services->listen('all', function($arg1, $arg2, $arg3) use (&$calls) {
            ++$calls['all'];
            $this->assertSame(['first' => 1, 'second' => 1, 'all' => 1], $calls);
        });

        // Récupère le service et vérifie que tous les listeners ont été appellés
        $this->assertSame($service, $services->get($id));
        $this->assertSame(['first' => 1, 'second' => 1, 'all' => 1], $calls);

        // Récupère à nouveau le service, aucun listener ne doit pas être appellé
        $this->assertSame($service, $services->get('service'));
        $this->assertSame(['first' => 1, 'second' => 1, 'all' => 1], $calls);
    }

    public function testReplace()
    {

        $old = new stdClass();
        $new = new stdClass();

        // Remplacement d'un service qui n'a pas encore été initialisé
        $services = new Services();
        $services->set('service', $old);
        $this->assertTrue($services->has('service'));
        $this->assertFalse($services->isLoaded('service'));

        $services->replace('service', $new);
        $this->assertTrue($services->has('service'));
        $this->assertFalse($services->isLoaded('service'));
        $this->assertSame($new, $services->get('service'));
        $this->assertTrue($services->isLoaded('service'));

        // Remplacement d'un service déjà initialisé
        $services = new Services();
        $services->set('service', $old);
        $this->assertTrue($services->has('service'));
        $this->assertSame($old, $services->get('service'));
        $this->assertTrue($services->isLoaded('service'));

        $services->replace('service', $new);
        $this->assertTrue($services->has('service'));
        $this->assertFalse($services->isLoaded('service')); // le service n'est plus initialisé
        $this->assertSame($new, $services->get('service'));
        $this->assertTrue($services->isLoaded('service'));

        // Remplacement d'un service déjà initialisé pour lequel on a des listeners
        $services = new Services();

        $calls = 0;
        $services->listen('service', function () use (&$calls) {
            ++$calls;
        });

        $services->set('service', $old);
        $this->assertSame($old, $services->get('service'));
        $this->assertTrue($services->isLoaded('service'));
        $this->assertSame(1, $calls);

        $services->replace('service', $new);
        $this->assertTrue($services->has('service'));
        $this->assertFalse($services->isLoaded('service')); // le service n'est plus initialisé
        $this->assertSame($new, $services->get('service'));
        $this->assertSame(2, $calls);
    }

    public function testReplaceInexistantService(): void
    {
        $services = new Services();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "service" not found');
        $services->replace('service', new stdClass());
    }

    public function testReplaceWithAlias(): void
    {
        $services = new Services();

        $services->set('service', new stdClass());
        $services->alias('alias', 'service');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "alias" not found');
        $services->replace('alias', new stdClass());
    }

    public function testReplaceFromListener(): void
    {
        $services = new Services();

        $old = new stdClass();
        $new = new stdClass();

        $calls = ['first' => 0, 'second' => 0, 'all' => 0];

        // Premier listener
        $services->listen('service', function(object $service, Services $services, string $id) use (&$calls) {
            ++$calls['first'];
        });

        // Second listener
        $services->listen('service', function(object $service, Services $services, string $id) use (&$calls, $old, $new) {
            ++$calls['second'];
            if ($id === 'service' && $service === $old) {
                $services->replace($id, $new);
            }
        });

        // Listener "all"
        $services->listen('all', function(object $service, Services $services, string $id) use (&$calls) {
            ++$calls['all'];
        });

        $services->set('service', $old);

        $this->assertSame($new, $services->get('service'));
        $this->assertSame(['first' => 2, 'second' => 2, 'all' => 1], $calls);

        // on initialise $old
        // - first est appellé ($first=1)
        // - second est appellé ($second=1)
        // - second appelle replace()
        // - get détecte le remplacement, stoppe les événements en cours ($all=0) et relance l'initialisation de $new
        // - first est appellé ($first=2)
        // - second est appellé ($second=2)
        // - all est appellé ($all=1)
    }

    public function testReplaceFromListenerInfiniteLoop(): void
    {
        $services = new Services();

        // à chaque fois qu'one ssaie d'initialiser "service", le listener associé remplace l'objet,
        // ce qui relance l'initialisation (i.e. on a une boucle infinie)
        $services->listen('service', function(object $service, Services $services, string $id) {
            $services->replace($id, new stdClass());
        });

        $services->set('service', new stdClass());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service "service" was replaced multiple times during initialization');

        $services->get('service');
    }

    public function testGetServices(): void
    {
        $services = new Services();

        $this->assertSame([], $services->getServices());

        $services->set('service1', new stdClass());
        $this->assertSame(['service1'], $services->getServices());

        $services->set('service2', new stdClass());
        $services->set('service3', new stdClass());
        $this->assertSame(['service1', 'service2', 'service3'], $services->getServices());
    }


    // public function testAll()
    // {
    //     // Vide
    //     $services = new Services();
    //     $this->assertSame([], $services->getServices());
    //     $this->assertFalse($services->has('bool'));
    //     $this->assertFalse($services->isLoaded('bool'));

    //     // Services
    //     $o = (object)['name' => 'smith', 'firstname' => 'john', 'age' => 27];
    //     $called = false;
    //     $all = [
    //         'null' => null, // null peut être un service valide
    //         'int' => 1,
    //         'bool' => true,
    //         'string' => 'hello',
    //         'array' => [1, 'a', true],
    //         'object' => $o,
    //         2016 => 'deux-mille-seize', // un nom de service n'est pas obligatoirement une chaine
    //         'closure' => function () use (& $called, $o) {
    //             $called = true;

    //             return $o;
    //         }
    //     ];

    //     // Vérifie que tous les services simples existent et sont instanciés
    //     $services = new Services($all);
    //     $this->assertSame($all, $services->getServices());
    //     foreach (['null', 'int', 'bool', 'string', 'array', 'object', 2016] as $service) {
    //         $this->assertTrue($services->has($service));
    //         $this->assertTrue($services->isLoaded($service));
    //         $this->assertSame($all[$service], $services->get($service));
    //     }

    //     // Vérifie que la closure n'a pas été appellée
    //     $this->assertFalse($called);
    //     $this->assertTrue($services->has('closure'));
    //     $this->assertFalse($services->isLoaded('closure'));

    //     // Exécute la closute
    //     $this->assertSame($o, $services->get('closure'));

    //     // Vérifie que la closure a été appellée
    //     $this->assertTrue($called);
    //     $this->assertTrue($services->has('closure'));
    //     $this->assertTrue($services->isLoaded('closure'));

    //     // Vérifie qu'elle n'est pas appellée une seconde fois
    //     $called = false;
    //     $this->assertSame($o, $services->get('closure'));
    //     $this->assertFalse($called);

    //     // Teste add()
    //     $this->assertSame($services, $services->add('other', 'service')); // fluent, retourne self
    //     $this->assertTrue($services->has('other'));
    //     $this->assertTrue($services->isLoaded('other'));
    //     $this->assertSame('service', $services->get('other'));
    // }

    // /**
    //  * Teste add() avec un service qui existe déjà.
    //  *
    //  * @expectedException InvalidArgumentException
    //  * @expectedExceptionMessage already registered
    //  */
    // public function testAddExistant()
    // {
    //     $services = new Services(['aaa' => 'Hi!']);

    //     $services->add('aaa', 'Oups!');
    // }

    // /**
    //  * Teste get() avec un service qui n'existe pas.
    //  *
    //  * @expectedException InvalidArgumentException
    //  * @expectedExceptionMessage not found
    //  */
    // public function testNotFound()
    // {
    //     $services = new Services(['aaa' => 'Hi!']);

    //     $services->get('bbb');
    // }
}
