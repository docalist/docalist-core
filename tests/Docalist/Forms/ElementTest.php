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

namespace Docalist\Tests\Forms;

use WP_UnitTestCase;
use Docalist\Forms\Container;
use Docalist\Type\Text;
use Docalist\Type\Composite;
use Docalist\Type\Collection;
use Docalist\Schema\Schema;
use InvalidArgumentException;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ElementTest extends WP_UnitTestCase
{
    /**
     * Crée un élément de formulaire (mock).
     *
     * @return ElementMock
     */
    protected function getElement()
    {
        return new ElementMock();
//         return (new ReflectionClass('Docalist\Tests\Forms\ElementMock'))
//             ->newInstanceArgs(func_get_args());
//        return $this->getMockForAbstractClass('Docalist\Forms\Element', func_get_args());
    }

    /**
     * Crée un containeur (mock).
     *
     * @return Container
     */
    protected function getContainer()
    {
        return new Container();
//        return $this->getMockForAbstractClass('Docalist\Forms\Container', func_get_args());
    }

    public function testGetSetName()
    {
        $element = $this->getElement();
        $this->assertNull($element->getName());

        $element->setName('login');
        $this->assertSame('login', $element->getName());

        $element->setName(null);
        $this->assertNull($element->getName());

        $element->setName('');
        $this->assertNull($element->getName()); // '' a été changé en null

        $element->setName(false);
        $this->assertNull($element->getName()); // false a été changé en null
    }

    /**
     * Teste setName avec un entier.
     */
    public function testSetBadName1()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid name');

        $this->getElement()->setName(12);
    }

    /**
     * Teste setName avec un tableau.
     */
    public function testSetBadName2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid name');

        $this->getElement()->setName(['login']);
    }


    public function testGetSetLabel()
    {
        $element = $this->getElement();
        $this->assertNull($element->getLabel());

        $element->setLabel('login');
        $this->assertSame('login', $element->getLabel());

        $element->setLabel(null);
        $this->assertNull($element->getLabel());

        $element->setLabel('');
        $this->assertNull($element->getLabel()); // '' a été changé en null

        $element->setLabel(false);
        $this->assertNull($element->getLabel()); // false a été changé en null
    }

    public function testGetSetDescription()
    {
        $element = $this->getElement();
        $this->assertNull($element->getDescription());

        $element->setDescription('login');
        $this->assertSame('login', $element->getDescription());

        $element->setDescription(null);
        $this->assertNull($element->getDescription());

        $element->setDescription('');
        $this->assertNull($element->getDescription()); // '' a été changé en null

        $element->setDescription(false);
        $this->assertNull($element->getDescription()); // false a été changé en null
    }

    public function testGetSetIsRepeatable()
    {
        $element = $this->getElement();
        $this->assertNull($element->getRepeatable());
        $this->assertFalse($element->isRepeatable());

        $element->setRepeatable(false);
        $this->assertFalse($element->getRepeatable());
        $this->assertFalse($element->isRepeatable());

        $element->setRepeatable(true);
        $this->assertTrue($element->getRepeatable());
        $this->assertTrue($element->isRepeatable());

        $element->setRepeatable(null);
        $this->assertNull($element->getRepeatable());
        $this->assertFalse($element->isRepeatable());
    }

    /**
     * Teste le binding d'un champ monovalué avec un type php.
     */
    public function testBindMonoWithPhpScalar()
    {
        // Crée un élément
        $element = $this->getElement();

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind('daniel'));

        // Vérifie que data a été correctement initialisé
        $this->assertSame('daniel', $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertNull($element->getRepeatable());
        $this->assertNull($element->getLabel());
        $this->assertNull($element->getDescription());
    }

    /**
     * Teste le binding d'un champ monovalué avec la valeur null.
     */
    public function testBindMonoWithNull()
    {
        // Crée un élément
        $element = $this->getElement();

        // La méthode clear() devra être appellée une fois (et une seule)
        // $form->expects($this->once())->method('clear');
        // ne marche pas ?

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind(null));

        // Vérifie que data vaut null
        $this->assertSame(null, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertNull($element->getRepeatable());
        $this->assertNull($element->getLabel());
        $this->assertNull($element->getDescription());
    }

    /**
     * Teste le binding d'un champ monovalué avec un tableau.
     */
    public function testBindMonoWithArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind([]);
    }

    /**
     * Teste le binding d'un champ monovalué avec un objet.
     */
    public function testBindMonoWithObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind((object) []);
    }

    /**
     * Teste le binding d'un champ répétable avec un type php.
     */
    public function testBindRepeatWithPhpArray()
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Fait le bind et vérifie que ça retourne $this
        $data = ['key' => 'daniel', null, 'ménard', true];
        $this->assertSame($element, $element->bind($data));

        // Vérifie que data a été correctement initialisé
        $this->assertSame($data, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertTrue($element->getRepeatable());
        $this->assertNull($element->getLabel());
        $this->assertNull($element->getDescription());

        // Vérifie que l'occurence a été définie
        $this->assertSame('key', $element->getOccurence());

        // Si on bind null, ça reset occurence
        $element->bind(null);
        $this->assertNull($element->getOccurence());
    }

    /**
     * Teste le binding d'un champ répétable avec la valeur null.
     */
    public function testBindRepeatWithNull()
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // La méthode clear() devra être appellée une fois (et une seule)
        // $form->expects($this->once())->method('clear');
        // ne marche pas ?

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind(null));

        // Vérifie que data vaut null
        $this->assertSame(null, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertTrue($element->getRepeatable());
        $this->assertNull($element->getLabel());
        $this->assertNull($element->getDescription());

        // Vérifie que l'occurence est toujours à null
        $this->assertNull($element->getOccurence());
    }

    /**
     * Teste le binding d'un champ répétable avec un scalaire.
     */
    public function testBindRepeatWithScalar()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected array');

        $this->getElement()->setRepeatable(true)->bind('daniel');
    }

    /**
     * Teste le binding d'un champ répétable avec un tableau vide (doit stocker null).
     */
    public function testBindRepeatWithEmptyArray()
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind([]));

        // Vérifie que data a été correctement initialisé
        $this->assertSame(null, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertTrue($element->getRepeatable());
        $this->assertNull($element->getLabel());
        $this->assertNull($element->getDescription());

        // Vérifie que l'occurence est à null
        $this->assertNull($element->getOccurence());
    }

    /**
     * Teste le binding d'un champ monovalué avec un scalaire Docalist sans schéma.
     */
    public function testBindMonoWithScalar()
    {
        // Crée un élément
        $element = $this->getElement();

        // Crée le type docalist
        $type = new Text('daniel');

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind($type));

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertNull($element->getLabel());
        $this->assertNull($element->getDescription());

        // Par contre, repeat est passé à false
        $this->assertFalse($element->getRepeatable());

        // Vérifie que data a été correctement initialisé
        $this->assertSame($type->getPhpValue(), $element->getData());

        // Si les propriétés étaient déjà initialisées, elle ne sont pas modifiées
        $element = $this->getElement()->setLabel('lbl')->setDescription('dsc')->bind($type);
        $this->assertSame('lbl', $element->getLabel());
        $this->assertSame('dsc', $element->getDescription());
        $this->assertFalse($element->getRepeatable());
    }

    /**
     * Teste le binding d'un champ monovalué avec une Collection.
     */
    public function testBindMonoWithCollection()
    {
        $data = new Collection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind($data);
    }

    /**
     * Teste le binding d'un champ monovalué avec un Composite.
     */
    public function testBindMonoWithComposite()
    {
        $data = new Composite();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind($data);
    }

    /**
     * Teste le binding d'un champ monovalué avec un scalaire Docalist possédant un schéma.
     */
    public function testBindMonoWithScalarSchema()
    {
        // Crée un élément
        $element = $this->getElement();

        // Crée le type docalist
        $schema = new Schema(['label' => 'lbl', 'description' => 'dsc']);
        $type = new Text('daniel', $schema);

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind($type));

        // Vérifie que data a été correctement initialisé
        $this->assertSame($type->getPhpValue(), $element->getData());

        // Vérifie que les propriétés ont été initialisées à partir du schéma
        $this->assertFalse($element->getRepeatable()); // repeat est passé de null à false
        $this->assertSame('lbl', $element->getLabel());
        $this->assertSame('dsc', $element->getDescription());
    }

    /**
     * Teste le binding d'un champ monovalué avec un scalaire Docalist possédant un schéma.
     */
    public function testBindRepeatWithCollectionSchema()
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Crée le type docalist
        $schema = new Schema(['label' => 'lbl', 'description' => 'dsc']);
        $type = new Collection(['daniel', 'ménard'], $schema);

        // Fait le bind et vérifie que ça retourne $this
        $this->assertSame($element, $element->bind($type));

        // Vérifie que data a été correctement initialisé
        $this->assertSame($type->getPhpValue(), $element->getData());

        // Vérifie que les propriétés ont été initialisées à partir du schéma
        $this->assertTrue($element->getRepeatable()); // repeat est passé de null à true
        $this->assertSame('lbl', $element->getLabel());
        $this->assertSame('dsc', $element->getDescription());

        // Vérifie que l'occurence a été définie
        $this->assertSame(0, $element->getOccurence());
    }

    public function testGetSetOccurence()
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Fait le bind et vérifie que ça retourne $this
        $data = ['firstname' => 'daniel', 'name' => 'ménard'];
        $this->assertSame($element, $element->bind($data));

        // Vérifie que data a été correctement initialisé
        $this->assertSame($data, $element->getData());

        // Vérifie que l'occurence a été définie
        $this->assertSame('firstname', $element->getOccurence());

        // Modifie l'occurence
        $this->assertSame($element, $element->setOccurence('name'));
        $this->assertSame('name', $element->getOccurence());

        // Modifie l'occurence
        $this->assertSame($element, $element->setOccurence('firstname'));
        $this->assertSame('firstname', $element->getOccurence());
    }

    /**
     * Teste setOccurence sur un élément non répétable.
     */
    public function testSetOccurenceOnNonRepeat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not repeatable');

        $this->getElement()->setOccurence(1);
    }

    /**
     * Teste setOccurence avec des données à null (bind non appellé par exemple).
     */
    public function testSetOccurenceOnNullData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not have data');

        $this->getElement()->setRepeatable(true)->setOccurence(12);
    }

    /**
     * Teste setOccurence avec une clé inexistante.
     */
    public function testSetInexistantOccurence()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not have data');

        $this->getElement()->setRepeatable(true)->bind(['a'])->setOccurence(12);
    }

    public function testGetControlName()
    {
        // 1. élément sans parent

        $element = $this->getElement();

        // Sans nom, monovalué
        $this->assertSame('', $element->getControlName());

        // Avec nom, monovalué
        $element->setName('tags');
        $this->assertSame('tags', $element->getControlName());

        // Avec nom, multivalué, pas de données
        $element->setRepeatable(true);
        $this->assertSame('tags[]', $element->getControlName());

        // Avec nom, multivalué, occurence int
        $element->bind(['red', 'blue']);
        $this->assertSame('tags[0]', $element->getControlName());

        // Avec nom, multivalué, occurence string
        $element->bind(['first' => 'red', 'second' => 'blue']);
        $this->assertSame('tags[first]', $element->getControlName());

        // Avec nom, multivalué, après changement occurence
        $element->setOccurence('second');
        $this->assertSame('tags[second]', $element->getControlName());


        // 2. Avec parent sans nom

        $element = $this->getElement()->setParent($this->getContainer());

        // Sans nom, monovalué
        $this->assertSame('', $element->getControlName());

        // Avec nom, monovalué
        $element->setName('tags');
        $this->assertSame('tags', $element->getControlName());

        // Avec nom, multivalué, pas de données
        $element->setRepeatable(true);
        $this->assertSame('tags[]', $element->getControlName());

        // Avec nom, multivalué, occurence int
        $element->bind(['red', 'blue']);
        $this->assertSame('tags[0]', $element->getControlName());

        // Avec nom, multivalué, occurence string
        $element->bind(['first' => 'red', 'second' => 'blue']);
        $this->assertSame('tags[first]', $element->getControlName());

        // Avec nom, multivalué, après changement occurence
        $element->setOccurence('second');
        $this->assertSame('tags[second]', $element->getControlName());


        // 3. Avec parent nommé

        $container = $this->getContainer()->setName('parent');
        $element = $this->getElement()->setParent($container);

        // Sans nom, monovalué
        $this->assertSame('', $element->getControlName());

        // Avec nom, monovalué
        $element->setName('tags');
        $this->assertSame('parent[tags]', $element->getControlName());

        // Avec nom, multivalué, pas de données
        $element->setRepeatable(true);
        $this->assertSame('parent[tags][]', $element->getControlName());

        // Avec nom, multivalué, occurence int
        $element->bind(['red', 'blue']);
        $this->assertSame('parent[tags][0]', $element->getControlName());

        // Avec nom, multivalué, occurence string
        $element->bind(['first' => 'red', 'second' => 'blue']);
        $this->assertSame('parent[tags][first]', $element->getControlName());

        // Avec nom, multivalué, après changement occurence
        $element->setOccurence('second');
        $this->assertSame('parent[tags][second]', $element->getControlName());


        // 4. Avec parent nommé et répétable, sans occurence

        $container = $this->getContainer()->setName('parent')->setRepeatable(true);
        $element = $this->getElement()->setParent($container);

        // Sans nom, monovalué
        $this->assertSame('', $element->getControlName());

        // Avec nom, monovalué
        $element->setName('tags');
        $this->assertSame('parent[][tags]', $element->getControlName());

        // Avec nom, multivalué, pas de données
        $element->setRepeatable(true);
        $this->assertSame('parent[][tags][]', $element->getControlName());

        // Avec nom, multivalué, occurence int
        $element->bind(['red', 'blue']);
        $this->assertSame('parent[][tags][0]', $element->getControlName());

        // Avec nom, multivalué, occurence string
        $element->bind(['first' => 'red', 'second' => 'blue']);
        $this->assertSame('parent[][tags][first]', $element->getControlName());

        // Avec nom, multivalué, après changement occurence
        $element->setOccurence('second');
        $this->assertSame('parent[][tags][second]', $element->getControlName());


        // 5. Avec parent nommé et répétable, avec occurence

        $container = $this->getContainer()->setName('parent')->setRepeatable(true)->bind(['a' => [], 'b' => []]);
        $element = $this->getElement()->setParent($container);

        // Sans nom, monovalué
        $this->assertSame('', $element->getControlName());

        // Avec nom, monovalué
        $element->setName('tags');
        $this->assertSame('parent[a][tags]', $element->getControlName());

        // Avec nom, multivalué, pas de données
        $element->setRepeatable(true);
        $this->assertSame('parent[a][tags][]', $element->getControlName());

        // Avec nom, multivalué, occurence int
        $element->bind(['red', 'blue']);
        $this->assertSame('parent[a][tags][0]', $element->getControlName());

        // Avec nom, multivalué, occurence string
        $element->bind(['first' => 'red', 'second' => 'blue']);
        $this->assertSame('parent[a][tags][first]', $element->getControlName());

        // Avec nom, multivalué, après changement occurence
        $element->setOccurence('second');
        $this->assertSame('parent[a][tags][second]', $element->getControlName());
    }
}
