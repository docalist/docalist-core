<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
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

        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim', 'a');
        $pipeline->appendOperation('md5', 'b');
        $this->assertSame(['a' => 'trim', 'b' => 'md5'], $pipeline->getOperations());
    }


    /**
     * Une exception TypeError est générée si une opération n'est pas un callable.
     */
    public function testConstructWithNotCallableOperation()
    {
        $pipeline = new StandardPipeline();

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be callable');

        $pipeline->appendOperation('a');
    }

    /**
     * Teste les méthodes setOperations, getOperations, hasOperation, getOperation, removeOperation, setOperation
     */
    public function testOperations()
    {
        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim', 'a');
        $pipeline->appendOperation('md5', 'b');

        // Vérifie que getOperations() retourne les opérations stockées par setOperations()
        $this->assertSame(['a' => 'trim', 'b' => 'md5'], $pipeline->getOperations());

        // Teste hasOperation()
        $this->assertTrue($pipeline->hasOperation('a'));
        $this->assertTrue($pipeline->hasOperation('b'));
        $this->assertFalse($pipeline->hasOperation('c'));
        $this->assertFalse($pipeline->hasOperation(0));

        // Teste getOperation()
        $this->assertSame('trim', $pipeline->getOperation('a'));
        $this->assertSame('md5', $pipeline->getOperation('b'));
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
    }

    /**
     * Une exception InvalidArgumentException si on appelle appendOperation() avec une clé qui existe déjà.
     */
    public function testAppendDuplicateKey()
    {
        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim', 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

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
    }

    /**
     * Une exception InvalidArgumentException si on appelle prependOperation() avec une clé qui existe déjà.
     */
    public function testPrependDuplicateKey()
    {
        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim', 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $pipeline->prependOperation('md5', 'a');
    }

    /**
     * Vérifie que l'ordre des opérations n'est pas changé si prependOperation() génère une exception.
     */
    public function testPrependDuplicateKeyOrder()
    {
        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim', 'a');
        $pipeline->appendOperation('md5', 'b');

        try {
            // en interne, prepend fait un array_reverse
            $pipeline->prependOperation('md5', 'a'); // duplicate key
        } catch (InvalidArgumentException $e) {
            // une exception a été générée, vérifie que l'ordre des opérations est toujours bon
            $this->assertSame(['a' => 'trim', 'b' => 'md5'], $pipeline->getOperations());
            return; // ok
        }

        $this->fail('aucune exception générée ?');
    }


    /**
     * Une exception InvalidArgumentException si on appelle getOperation() avec une clé inexistante.
     */
    public function testGetOperationInvalidKey()
    {
        $pipeline = new StandardPipeline();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $pipeline->getOperation('b');
    }

    /**
     * Teste la méthode process.
     */
    public function testProcess()
    {
        // crée un pipeline qui teste à peu près tout : générateurs, transformeurs, filtres, etc.
        $pipeline = new StandardPipeline();

        // op1 (generator) : génère deux items pour chaque item reçu (exemple : 'a' => ['a1', 'a2'])
        $pipeline->appendOperation(function ($item) {
            yield $item . '1';
            yield $item . '2';
        });

        // op2 (transformer) : met l'item en tout maju (exemple : 'a1' => 'A1')
        $pipeline->appendOperation('strtoupper');

        // op3 (transformer) : dédouble l'item (exemple : 'A1' => 'A1A1')
        $pipeline->appendOperation(function ($item) {
            return $item . $item;
        });

        // op4 (pipeline in pipeline) : propercase (exemple 'A1A1' => 'a1a1' => 'A1a1')
        $inner = new StandardPipeline();
        $inner->appendOperation('strtolower');
        $inner->appendOperation('ucfirst');
        $pipeline->appendOperation($inner);

        // op5 (filter) : supprime l'item 'B1b1'
        $pipeline->appendOperation(function ($item) {
            return $item === 'B1b1' ? null : $item;
        });

        // op6 (filter) : yield tous les items, sauf 'A1a1'
        $pipeline->appendOperation(function ($item) {
            if ($item !== 'A1a1') {
                yield $item;
            }
        });

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

        $pipeline = new StandardPipeline();
        $pipeline->appendOperation(function () use (& $started) {
            $started = true;
        });

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
        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim');
        $pipeline->appendOperation('md5');

        $result = $pipeline->process([]);
        $this->assertSame([], iterator_to_array($result));
    }

    /**
     * Vérifie que process() retourne un tableau vide si tous les éléments sont filtrés.
     */
    public function testProcessFilterAll()
    {
        $pipeline = new StandardPipeline();
        $pipeline->appendOperation('trim');
        $pipeline->appendOperation('md5');
        $pipeline->appendOperation(function () {
            return null;
        });

        $result = $pipeline->process(['a', 'b', 'c']);
        $this->assertSame([], iterator_to_array($result));
    }
}
