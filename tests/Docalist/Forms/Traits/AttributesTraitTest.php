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
use Docalist\Forms\Element;
use Docalist\Forms\Traits\AttributesTrait;
use InvalidArgumentException;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AttributesTraitTest extends WP_UnitTestCase
{
    /**
     * Crée un item (mock).
     *
     * @return Element
     */
    protected function getTrait()
    {
        return $this->getMockForTrait(AttributesTrait::class, func_get_args());
    }

    public function testHasGetSetAttributes()
    {
        $attr = ['class' => 'required', 'id' => '',  'checked' => true, 'selected' => false];
        $stored = ['class' => 'required', 'id' => '', 'checked' => true];

        // Initiallement les attributs sont vides
        $element = $this->getTrait();
        $this->assertSame([], $element->getAttributes());

        // Définit des attributs
        $element = $this->getTrait()->setAttributes($attr);
        $this->assertSame($stored, $element->getAttributes());

        // hasAttribute
        $this->assertFalse($element->hasAttribute('name')); // non présent dans attr
        $this->assertTrue($element->hasAttribute('class'));
        $this->assertTrue($element->hasAttribute('id')); // vide mais stocké quand même
        $this->assertTrue($element->hasAttribute('checked'));
        $this->assertFalse($element->hasAttribute('selected')); // false car à false dans attr

        // getAttribute
        $this->assertNull($element->getAttribute('name')); // null car n'existe pas dans attr
        $this->assertSame('required', $element->getAttribute('class'));
        $this->assertSame('', $element->getAttribute('id'));
        $this->assertSame(true, $element->getAttribute('checked'));
        $this->assertNull($element->getAttribute('selected'));

        // setAttribute
        $element->setAttribute('readonly'); // on, méthode 1
        $this->assertTrue($element->hasAttribute('readonly'));
        $this->assertSame(true, $element->getAttribute('readonly'));

        $element->setAttribute('readonly', false); // off, méthode 1
        $this->assertFalse($element->hasAttribute('readonly'));
        $this->assertNull($element->getAttribute('readonly'));

        $element->setAttribute('readonly', true); // on, méthode 2
        $this->assertTrue($element->hasAttribute('readonly'));
        $this->assertSame(true, $element->getAttribute('readonly'));

        $element->setAttribute('readonly', '');
        $this->assertTrue($element->hasAttribute('readonly'));
        $this->assertSame('', $element->getAttribute('readonly'));

        $element->setAttribute('readonly', 'readonly');  // on, méthode 3
        $this->assertTrue($element->hasAttribute('readonly'));
        $this->assertSame('readonly', $element->getAttribute('readonly'));

        $element->setAttribute('readonly', null); // off, méthode 3
        $this->assertFalse($element->hasAttribute('readonly'));
        $this->assertNull($element->getAttribute('readonly'));

        $element->setAttribute('readonly', 'readonly');  // on, méthode 3
        $this->assertTrue($element->hasAttribute('readonly'));
        $this->assertSame('readonly', $element->getAttribute('readonly'));

//         $element->setAttribute('readonly', 'any other value'); // off, méthode 4
//         $this->assertFalse($element->hasAttribute('readonly'));
//         $this->assertNull($element->getAttribute('readonly'));
        $element->removeAttribute('readonly');

        $element->setAttribute('name', 'test');  // set, méthode 1
        $this->assertTrue($element->hasAttribute('name'));
        $this->assertSame('test', $element->getAttribute('name'));

        $element->setAttribute('name', null); // unset, méthode 1
        $this->assertFalse($element->hasAttribute('name'));
        $this->assertNull($element->getAttribute('name'));

        $element->setAttribute('name', 'test');  // set, méthode 1
        $this->assertTrue($element->hasAttribute('name'));
        $this->assertSame('test', $element->getAttribute('name'));

        $element->setAttribute('name', '');
        $this->assertTrue($element->hasAttribute('name'));
        $this->assertSame('', $element->getAttribute('name'));

        $element->setAttribute('name', 'test');  // set, méthode 1
        $this->assertTrue($element->hasAttribute('name'));
        $this->assertSame('test', $element->getAttribute('name'));

        $element->setAttribute('name', false); // unset, méthode 3
        $this->assertFalse($element->hasAttribute('name'));
        $this->assertNull($element->getAttribute('name'));

        $element->removeAttribute('id');

        // removeAttribute (à ce stade notre élément contient les attributs indiqués dans $stored : class et checked)
        $element->removeAttribute('class');
        $this->assertFalse($element->hasAttribute('class'));
        $this->assertNull($element->getAttribute('class'));

        $element->removeAttribute('checked');
        $this->assertFalse($element->hasAttribute('checked'));
        $this->assertNull($element->getAttribute('checked'));

        $this->assertSame([], $element->getAttributes());
    }


    public function testGetSetHasToggleClass()
    {
        $element = $this->getTrait();
        $this->assertFalse($element->hasAttribute('class'));

        $element->addClass('required');
        $this->assertTrue($element->hasAttribute('class'));
        $this->assertSame('required', $element->getAttribute('class'));
        $this->assertTrue($element->hasClass('required'));
        $this->assertFalse($element->hasClass('toto'));
        $this->assertTrue($element->hasClass('non not nein     required'));

        $element->addClass('  invalid   red js ');
        $this->assertTrue($element->hasAttribute('class'));
        $this->assertSame('required invalid red js', $element->getAttribute('class'));
        $this->assertTrue($element->hasClass('invalid'));
        $this->assertTrue($element->hasClass('red'));
        $this->assertTrue($element->hasClass('js'));
        $this->assertFalse($element->hasClass('valid'));

        $element->removeClass('red');
        $this->assertTrue($element->hasAttribute('class'));
        $this->assertSame('required invalid js', $element->getAttribute('class'));
        $this->assertTrue($element->hasClass('required'));
        $this->assertTrue($element->hasClass('invalid'));
        $this->assertTrue($element->hasClass('js'));
        $this->assertFalse($element->hasClass('red'));

        $element->removeClass(' required  red    js');
        $this->assertTrue($element->hasAttribute('class'));
        $this->assertSame('invalid', $element->getAttribute('class'));
        $this->assertFalse($element->hasClass('required'));
        $this->assertTrue($element->hasClass('invalid'));
        $this->assertFalse($element->hasClass('js'));
        $this->assertFalse($element->hasClass('red'));

        $element->toggleClass(' required    invalid   js');
        $this->assertTrue($element->hasAttribute('class'));
        $this->assertSame('required js', $element->getAttribute('class'));
        $this->assertTrue($element->hasClass('required'));
        $this->assertFalse($element->hasClass('invalid'));
        $this->assertTrue($element->hasClass('js'));

        $element->removeClass();
        $this->assertFalse($element->hasClass('required'));
        $this->assertFalse($element->hasClass('js'));
        $this->assertSame(null, $element->getAttribute('class'));

        $element->removeClass();
        $this->assertSame(null, $element->getAttribute('class'));

        $element->toggleClass('required invalid js');
        $element->removeClass('required invalid js shortcircuit because empty');
    }

    /**
     * Vérifie qu'une exception est générée si la valeur de l'attribut n'est pas un scalaire.
     */
    public function testInvalidAttributeName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value');

        $this->getTrait()->setAttribute('class', []);
    }
}
