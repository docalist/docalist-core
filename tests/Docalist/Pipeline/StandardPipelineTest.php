<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Json;

use PHPUnit_Framework_TestCase;
use Docalist\Pipeline\StandardPipeline;
use ArrayIterator;
use InvalidArgumentException;
use TypeError;

/**
 * Teste la classe StandardPipeline.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class StandardPipelineTest extends PHPUnit_Framework_TestCase
{
    /**
     * Si on passe un tableau à un pipeline vide, il retourne le tableau inchangé.
     */
    public function testEmptyPipelineWithArray()
    {
        $pipeline = new StandardPipeline();

        $in = ['a', 'last' => 'b'];
        $out = $pipeline->process($in);
        $this->assertSame($out, $in);
    }

    /**
     * Si on passe un itérateur à un pipeline vide, il retourne l'itérateur inchangé.
     */
    public function testEmptyPipelineWithIterator()
    {
        $pipeline = new StandardPipeline();

        $in = new ArrayIterator(['a', 'b']);
        $out = $pipeline->process($in);
        $this->assertSame($out, $in);
    }

    /**
     * Si on passe un générateur à un pipeline vide, il retourne le générateur inchangé.
     */
    public function testEmptyPipelineWithGenerator()
    {
        $pipeline = new StandardPipeline();

        $generate = function () {
            yield 'a';
            yield 'last' => 'b';
        };

        $in = $generate();
        $out = $pipeline->process($in);
        $this->assertSame($out, $in);
    }

    /**
     * Vérifie que les opérations sont bien stockées quand on les passe en paramètre à __construct
     */
    public function testConstruct()
    {
        $pipeline = new StandardPipeline();
        $this->assertSame([], $pipeline->getOperations());

        $pipeline = new StandardPipeline(['a' => 'trim', 'b' => 'md5']);
        $this->assertSame(['a' => 'trim', 'b' => 'md5'], $pipeline->getOperations());
    }


    /**
     * Une exception TypeError est générée si une opération n'est pas un callable.
     *
     * @expectedException TypeError
     * @expectedExceptionMessage must be callable
     */
    public function testConstructWithNotCallableOperation()
    {
        $pipeline = new StandardPipeline(['a']);
    }

    /**
     * Teste les méthodes setOperations, getOperations, hasOperation, getOperation, removeOperation, setOperation
     */
    public function testOperations()
    {
        $pipeline = new StandardPipeline();

        // Vérifie que getOperations() retourne les opérations stockées par setOperations()
        $pipeline->setOperations(['a' => 'trim', 'b' => 'md5']);
        $this->assertSame(['a' => 'trim', 'b' => 'md5'], $pipeline->getOperations());

        // Teste hasOperation()
        $this->assertTrue($pipeline->hasOperation('a'));
        $this->assertTrue($pipeline->hasOperation('b'));
        $this->assertFalse($pipeline->hasOperation('c'));
        $this->assertFalse($pipeline->hasOperation(0));

        // Teste getOperation()
        $this->assertSame('trim', $pipeline->getOperation('a'));
        $this->assertSame('md5', $pipeline->getOperation('b'));

        // Vérifie que removeOperation() supprime les opérations
        $pipeline->removeOperation('b');
        $this->assertFalse($pipeline->hasOperation('b'));
        $this->assertSame(['a' => 'trim'], $pipeline->getOperations());
        $this->assertTrue($pipeline->hasOperation('a'));

        // Vérifie que setOperation() modifie l'opération indiquée
        $pipeline->setOperation('a', 'chop');
        $this->assertSame('chop', $pipeline->getOperation('a'));
        $this->assertSame(['a' => 'chop'], $pipeline->getOperations());
    }

    /**
     * Teste appendOperation() avec des clés.
     */
    public function testAppendWithKey()
    {
        $pipeline = new StandardPipeline();

        // Append ajoute les opérations à la fin de la liste, tient compte de la clé indiquée et retourn $this
        $pipeline->appendOperation('trim', 'a');
        $pipeline->appendOperation('ucfirst', 'b');
        $pipeline->appendOperation('md5', 'c');
        $this->assertSame(['a' => 'trim', 'b' => 'ucfirst', 'c' => 'md5'], $pipeline->getOperations());

        // Si on ajoute une opération sans clé, le premier numéro attribué est zéro
        $pipeline->appendOperation('strrev');
        $this->assertSame(['a' => 'trim', 'b' => 'ucfirst', 'c' => 'md5', 0 => 'strrev'], $pipeline->getOperations());
    }

    /**
     * Teste appendOperation() sans clés.
     */
    public function testAppendWithoutKey()
    {
        $pipeline = new StandardPipeline();

        // Append ajoute les opérations à la fin de la liste et numérote séquentiellement à partir de zéro
        $pipeline->appendOperation('trim');
        $pipeline->appendOperation('ucfirst');
        $pipeline->appendOperation('md5');
        $this->assertSame([0 => 'trim', 1 => 'ucfirst', 2 => 'md5'], $pipeline->getOperations());

        // Si on supprime une opération, la clé n'est pas réutilisée
        $pipeline->removeOperation(0);
        $pipeline->appendOperation('strrev');
        $this->assertSame([1 => 'ucfirst', 2 => 'md5', 3 => 'strrev'], $pipeline->getOperations());
    }

    /**
     * Une exception InvalidArgumentException si on appelle appendOperation() avec une clé qui existe déjà.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage already exists
     */
    public function testAppendDuplicateKey()
    {
        $pipeline = new StandardPipeline(['a' => 'trim']);
        $pipeline->appendOperation('md5', 'a');
    }

    /**
     * Teste prependOperation() avec des clés.
     */
    public function testPrependWithKey()
    {
        $pipeline = new StandardPipeline();

        // Prepend ajoute les opérations au début de la liste et tient compte de la clé indiquée
        $pipeline->prependOperation('md5', 'a');
        $pipeline->prependOperation('ucfirst', 'b');
        $pipeline->prependOperation('trim', 'c');
        $this->assertSame(['c' => 'trim', 'b' => 'ucfirst', 'a' => 'md5'], $pipeline->getOperations());

        // Si on ajoute une opération sans clé, le premier numéro attribué est zéro
        $pipeline->prependOperation('strrev');
        $this->assertSame([0 => 'strrev', 'c' => 'trim', 'b' => 'ucfirst', 'a' => 'md5'], $pipeline->getOperations());
    }

    /**
     * Teste prependOperation() sans clés.
     */
    public function testPrependWithoutKey()
    {
        $pipeline = new StandardPipeline();

        // Prepend ajoute les opérations au début de la liste et numérote séquentiellement à partir de zéro
        $pipeline->prependOperation('md5');
        $pipeline->prependOperation('ucfirst');
        $pipeline->prependOperation('trim');
        $this->assertSame([2 => 'trim', 1 => 'ucfirst', 0 => 'md5'], $pipeline->getOperations());

        // Si on supprime une opération, la clé n'est pas réutilisée
        $pipeline->removeOperation(0);
        $pipeline->prependOperation('strrev');
        $this->assertSame([3 => 'strrev', 2 => 'trim', 1 => 'ucfirst'], $pipeline->getOperations());
    }

    /**
     * Une exception InvalidArgumentException si on appelle prependOperation() avec une clé qui existe déjà.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage already exists
     */
    public function testPrependDuplicateKey()
    {
        $pipeline = new StandardPipeline(['a' => 'trim']);
        $pipeline->prependOperation('md5', 'a');
    }

    /**
     * Vérifie que l'ordre des opérations n'est pas changé si prependOperation() génère une exception.
     */
    public function testPrependDuplicateKeyOrder()
    {
        $pipeline = new StandardPipeline(['a' => 'trim', 'b' => 'md5']);
        try {
            // en interne, prepend fait un array_reverse
            $pipeline->prependOperation('md5', 'a');
        } catch (InvalidArgumentException $e) {
            // une exception a été générée, vérifie que l'ordre des opérations est toujours bon
            $this->assertSame(['a' => 'trim', 'b' => 'md5'], $pipeline->getOperations());
            return; // ok
        }

        $this->fail('aucune exception générée ?');
    }


    /**
     * Une exception InvalidArgumentException si on appelle getOperation() avec une clé inexistante.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage not found
     */
    public function testGetOperationInvalidKey()
    {
        $pipeline = new StandardPipeline(['a' => 'trim']);
        $pipeline->getOperation('b');
    }

    /**
     * Teste la méthode process.
     */
    public function testProcess()
    {
        // crée un pipeline qui teste à peu près tout : générateurs, transformeurs, filtres, etc.
        $pipeline = new StandardPipeline([
            // op1 (generator) : génère deux items pour chaque item reçu (exemple : 'a' => ['a1', 'a2'])
            'op1' => function ($item) {
                yield $item . '1';
                yield $item . '2';
            },

            // op2 (transformer) : met l'item en tout maju (exemple : 'a1' => 'A1')
            'op2' => 'strtoupper',

            // op3 (transformer) : dédouble l'item (exemple : 'A1' => 'A1A1')
            'op3' => function ($item) {
                return $item . $item;
            },

            // op4 (pipeline in pipeline) : propercase (exemple 'A1A1' => 'a1a1' => 'A1a1')
            'op4' => new StandardPipeline(['strtolower', 'ucfirst']),

            // op5 (filter) : supprime l'item 'B1b1'
            'op5' => function ($item) {
                return $item === 'B1b1' ? null : $item;
            },

            // op6 (filter) : yield tous les items, sauf 'A1a1'
            'op6' => function ($item) {
                if ($item !== 'A1a1') {
                    yield $item;
                }
            }
        ]);

        $input = ['a', 'b'];
        //      1               2               3                   4                   5                   6
        // 'a' -> ['a1', 'a2'] -> ['A1', 'A2'] -> ['A1A1', 'A2A2'] -> ['A1a1', 'A2a2'] -> ['A1a1', 'A2a2'] -> ['A2a2']
        // 'b' -> ['b1', 'b2'] -> ['B1', 'B2'] -> ['B1B1', 'B2B2'] -> ['B1b1', 'B2b2'] -> ['B2b2']         -> ['B2b2']
        $output = ['A2a2', 'B2b2'];

        $this->assertSame($output, iterator_to_array($pipeline->process($input), false));
    }

    /**
     * Vérifie que process() est "paresseux" : le traitement ne démarre que lorsqu'on itère sur les résultats.
     */
    public function testProcessLaziness()
    {
        $started = false;

        $pipeline = new StandardPipeline([
            function () use (& $started) {
                $started = true;
            }
        ]);

        $result = $pipeline->process(['a', 'b', 'c']);
        $this->assertFalse($started);

        iterator_to_array($result);
        $this->assertTrue($started);
    }

    /**
     * Vérifie que process() retourne un tableau vide si on lui passe une collection vide.
     */
    public function testProcessWithEmptyCollection()
    {
        $pipeline = new StandardPipeline(['trim', 'md5']);

        $result = $pipeline->process([]);
        $this->assertSame([], iterator_to_array($result));
    }

    /**
     * Vérifie que process() retourne un tableau vide si tous les éléments sont filtrés.
     */
    public function testProcessFilterAll()
    {
        $pipeline = new StandardPipeline([
            'trim',
            'md5',
            function () {
                return null;
            }
        ]);

        $result = $pipeline->process(['a', 'b', 'c']);
        $this->assertSame([], iterator_to_array($result));
    }
}
