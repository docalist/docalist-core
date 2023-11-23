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

use Docalist\Forms\Container;
use Docalist\Forms\Element;
use Docalist\Schema\Schema;
use Docalist\Test\DocalistTestCase;
use Docalist\Type\Collection;
use Docalist\Type\Composite;
use Docalist\Type\Text;
use InvalidArgumentException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ElementTest extends DocalistTestCase
{
    /**
     * Crée un élément.
     */
    protected function getElement(string $name = ''): ElementMock
    {
        return new ElementMock($name);
        // return new class($name) extends Element {
        //     public function getControlName(): string
        //     {
        //         return parent::getControlName();
        //     }

        //     public function setOccurence(int|string $occurence): void
        //     {
        //         parent::setOccurence($occurence);
        //     }
        // };
    }

    /**
     * Crée un containeur (mock).
     */
    protected function getContainer(string $name = ''): Container
    {
        return new Container($name);
    }

    public function testGetSetName(): void
    {
        $element = $this->getElement();
        $this->assertSame('', $element->getName());

        $element->setName('login');
        $this->assertSame('login', $element->getName());

        $element->setName('');
        $this->assertSame('', $element->getName());
    }

    public function testGetSetLabel(): void
    {
        $element = $this->getElement();
        $this->assertSame('', $element->getLabel());

        $element->setLabel('login');
        $this->assertSame('login', $element->getLabel());

        $element->setLabel('');
        $this->assertSame('', $element->getLabel());
    }

    public function testGetSetDescription(): void
    {
        $element = $this->getElement();
        $this->assertSame('', $element->getDescription());

        $element->setDescription('login');
        $this->assertSame('login', $element->getDescription());

        $element->setDescription('');
        $this->assertSame('', $element->getDescription());
    }

    public function testGetSetIsRepeatable(): void
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
    public function testBindMonoWithPhpScalar(): void
    {
        // Crée un élément
        $element = $this->getElement();

        // Fait le bind et vérifie que data a été correctement initialisé
        $element->bind('daniel');
        $this->assertSame('daniel', $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertNull($element->getRepeatable());
        $this->assertSame('', $element->getLabel());
        $this->assertSame('', $element->getDescription());
    }

    /**
     * Teste le binding d'un champ monovalué avec la valeur null.
     */
    public function testBindMonoWithNull(): void
    {
        // Crée un élément
        $element = $this->getElement();

        // La méthode clear() devra être appellée une fois (et une seule)
        // $form->expects($this->once())->method('clear');
        // ne marche pas ?

        // Fait le bind et vérifie que data vaut null
        $element->bind(null);
        $this->assertSame(null, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertNull($element->getRepeatable());
        $this->assertSame('', $element->getLabel());
        $this->assertSame('', $element->getDescription());
    }

    /**
     * Teste le binding d'un champ monovalué avec un tableau.
     */
    public function testBindMonoWithArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind([]);
    }

    /**
     * Teste le binding d'un champ répétable avec un type php.
     */
    public function testBindRepeatWithPhpArray(): void
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Fait le bind et vérifie que data a été correctement initialisé
        $data = ['key' => 'daniel', null, 'ménard', true];
        $element->bind($data);
        $this->assertSame($data, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertTrue($element->getRepeatable());
        $this->assertSame('', $element->getLabel());
        $this->assertSame('', $element->getDescription());

        // Vérifie que l'occurence a été définie
        $this->assertSame('key', $element->getOccurence());

        // Si on bind null, ça reset occurence
        $element->bind(null);
        $this->assertNull($element->getOccurence());
    }

    /**
     * Teste le binding d'un champ répétable avec la valeur null.
     */
    public function testBindRepeatWithNull(): void
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // La méthode clear() devra être appellée une fois (et une seule)
        // $form->expects($this->once())->method('clear');
        // ne marche pas ?

        // Fait le bind et vérifie que data vaut null
        $element->bind(null);
        $this->assertSame(null, $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertTrue($element->getRepeatable());
        $this->assertSame('', $element->getLabel());
        $this->assertSame('', $element->getDescription());

        // Vérifie que l'occurence est toujours à null
        $this->assertNull($element->getOccurence());
    }

    // /**
    //  * Teste le binding d'un champ répétable avec un scalaire.
    //  */
    // public function testBindRepeatWithScalar(): void
    // {
    //     $this->expectException(InvalidArgumentException::class);
    //     $this->expectExceptionMessage('expected array');

    //     $this->getElement()->setRepeatable(true)->bind('daniel');
    // }

    /**
     * Teste le binding d'un champ répétable avec un tableau vide (doit stocker null).
     */
    public function testBindRepeatWithEmptyArray(): void
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Fait le bind et vérifie que data a été correctement initialisé
        $element->bind([]);
        $this->assertNull($element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertTrue($element->getRepeatable());
        $this->assertSame('', $element->getLabel());
        $this->assertSame('', $element->getDescription());

        // Vérifie que l'occurence est à null
        $this->assertNull($element->getOccurence());
    }

    /**
     * Teste le binding d'un champ monovalué avec un scalaire Docalist sans schéma.
     */
    public function testBindMonoWithScalar(): void
    {
        // Crée un élément
        $element = $this->getElement();

        // Crée le type docalist
        $type = new Text('daniel');

        // Fait le bind et vérifie que data a été correctement initialisé
        $element->bind($type);
        $this->assertSame($type->getPhpValue(), $element->getData());

        // Vérifie que les propriétés n'ont pas été modifiées
        $this->assertSame('', $element->getLabel());
        $this->assertSame('', $element->getDescription());

        // Par contre, repeat est passé à false
        $this->assertFalse($element->getRepeatable());

        // Si les propriétés étaient déjà initialisées, elle ne sont pas modifiées
        $element = $this->getElement()->setLabel('lbl')->setDescription('dsc');
        $element->bind($type);
        $this->assertSame('lbl', $element->getLabel());
        $this->assertSame('dsc', $element->getDescription());
        $this->assertFalse($element->getRepeatable());
    }

    /**
     * Teste le binding d'un champ monovalué avec une Collection.
     */
    public function testBindMonoWithCollection(): void
    {
        $data = new Collection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind($data);
    }

    /**
     * Teste le binding d'un champ monovalué avec un Composite.
     */
    public function testBindMonoWithComposite(): void
    {
        $data = new class() extends Composite {};

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expected scalar');

        $this->getElement()->bind($data);
    }

    /**
     * Teste le binding d'un champ monovalué avec un scalaire Docalist possédant un schéma.
     */
    public function testBindMonoWithScalarSchema(): void
    {
        // Crée un élément
        $element = $this->getElement();

        // Crée le type docalist
        $schema = new Schema(['label' => 'lbl', 'description' => 'dsc']);
        $type = new Text('daniel', $schema);

        // Fait le bind et vérifie que data a été correctement initialisé
        $element->bind($type);
        $this->assertSame($type->getPhpValue(), $element->getData());

        // Vérifie que les propriétés ont été initialisées à partir du schéma
        $this->assertFalse($element->getRepeatable()); // repeat est passé de null à false
        $this->assertSame('lbl', $element->getLabel());
        $this->assertSame('dsc', $element->getDescription());
    }

    /**
     * Teste le binding d'un champ monovalué avec un scalaire Docalist possédant un schéma.
     */
    public function testBindRepeatWithCollectionSchema(): void
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Crée le type docalist
        $schema = new Schema(['label' => 'lbl', 'description' => 'dsc']);
        $type = new Collection(['daniel', 'ménard'], $schema);

        // Fait le bind et vérifie que data a été correctement initialisé
        $element->bind($type);
        $this->assertSame($type->getPhpValue(), $element->getData());

        // Vérifie que les propriétés ont été initialisées à partir du schéma
        $this->assertTrue($element->getRepeatable()); // repeat est passé de null à true
        $this->assertSame('lbl', $element->getLabel());
        $this->assertSame('dsc', $element->getDescription());

        // Vérifie que l'occurence a été définie
        $this->assertSame(0, $element->getOccurence());
    }

    public function testGetSetOccurence(): void
    {
        // Crée un élément répétable
        $element = $this->getElement()->setRepeatable(true);

        // Fait le bind et vérifie que data a été correctement initialisé
        $data = ['firstname' => 'daniel', 'name' => 'ménard'];
        $element->bind($data);

        // Vérifie que data a été correctement initialisé
        $this->assertSame($data, $element->getData());

        // Vérifie que l'occurence a été définie
        $this->assertSame('firstname', $element->getOccurence());

        // Modifie l'occurence
        $element->setOccurence('name');
        $this->assertSame('name', $element->getOccurence());

        // Modifie l'occurence
        $element->setOccurence('firstname');
        $this->assertSame('firstname', $element->getOccurence());
    }

    /**
     * Teste setOccurence sur un élément non répétable.
     */
    public function testSetOccurenceOnNonRepeat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not repeatable');

        $this->getElement()->setOccurence(1);
    }

    /**
     * Teste setOccurence avec des données à null (bind non appellé par exemple).
     */
    public function testSetOccurenceOnNullData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not have data');

        $this->getElement()->setRepeatable(true)->setOccurence(12);
    }

    /**
     * Teste setOccurence avec une clé inexistante.
     */
    public function testSetInexistantOccurence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('do not have data');

        $this->getElement()->setRepeatable(true)->bind(['a'])->setOccurence(12);
    }

    public function testGetControlName(): void
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

        $container = $this->getContainer('parent')->setName('parent')->setRepeatable(true)->bind(['a' => [], 'b' => []]);
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

    public function testTequiredModes(): void
    {
        $element = $this->getElement();

        $modes = $element->requiredModes();
        $this->assertIsArray($modes);
    }

    public function testGetSetTequired(): void
    {
        $element = $this->getElement();

        $this->assertSame('', $element->getRequired());

        $modes = $element->requiredModes();
        foreach ($modes as $mode => $label) {
            $element->setRequired($mode);
            $this->assertSame($mode, $element->getRequired());
        }
    }

    public function testSetTequiredBadMode(): void
    {
        $element = $this->getElement();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid required mode');

        $element->setRequired('bad mode');
    }

    public function testGenerateId(): void
    {
        $element = $this->getElement('');
        $this->assertSame('elementmock', $this->callNonPublic($element, 'generateID'));

        $element = $this->getElement('mytest');
        $this->assertSame('mytest', $this->callNonPublic($element, 'generateID'));

        $element = $this->getElement('mytest');
        $element->setRepeatable(true);
        $this->assertSame('mytest', $this->callNonPublic($element, 'generateID'));

        $group1 = $this->getContainer('group1');
        $group1->add($element);
        $this->assertSame('group1-mytest', $this->callNonPublic($element, 'generateID'));

        $group2 = $this->getContainer('group2');
        $group2->add($group1);
        $this->assertSame('group2-group1-mytest', $this->callNonPublic($element, 'generateID'));
    }

    public function testGetOccurences(): void
    {
        $element = $this->getElement();
        $this->assertSame([null], $this->callNonPublic($element, 'getOccurences'));

        $element->setRepeatable(true);
        $this->assertSame([null], $this->callNonPublic($element, 'getOccurences'));

        $element = $this->getElement();
        $element->bind('coucou');
        $this->assertSame(['coucou'], $this->callNonPublic($element, 'getOccurences'));

        $element = $this->getElement();
        $element->setRepeatable(true);
        $element->bind('coucou');
        $this->assertSame(['coucou'], $this->callNonPublic($element, 'getOccurences'));
    }
}
