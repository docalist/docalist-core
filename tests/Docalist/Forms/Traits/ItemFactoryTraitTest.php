<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Forms\Traits;

use WP_UnitTestCase;
use Docalist\Forms\Item;
use Docalist\Forms\Container;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ItemFactoryTraitTest extends WP_UnitTestCase
{
    /**
     * Crée un container.
     *
     * @return Container
     */
    protected function getForm()
    {
        return new Container();
    }

    public function provider()
    {
        /*
         * Chaque élément du tableau contient :
         * - le nom de la factory method à appeller
         * - un tableau contenant les paramètres à passer
         * - le type de la classe Docalist attendue en retour (le namespace est ajouté automatiquement)
         * - un tableau de méthodes à tester
         *
         * Nomenclature :
         * - a : attributs
         * - c : contenu
         * - n : nom d'élément
         * - t : nom de tag
         */
        $a = ['class' => 'required'];
        $ai = ['type' => 'text'] + $a;
        $ap = ['type' => 'password'] + $a;
        $ah = ['type' => 'hidden'] + $a;
        $at = ['rows' => 10, 'cols' => 50] + $a;
        $ac = ['type' => 'checkbox', 'value' => 1] + $a;
        $ar = ['type' => 'radio'] + $a;
        $ab = ['type' => 'button'] + $a;
        $as = ['type' => 'submit'] + $a;
        $az = ['type' => 'reset'] + $a;
        return [
            ['comment'  , ['c']         , 'Comment'  , ['getContent']],
            ['html'     , ['c']         , 'Html'     , ['getContent']],
            ['tag'      , ['t', 'c', $a], 'Tag'      , ['getTag', 'getContent', 'getAttributes', 'getTag' => 'div']],
            ['p'        , ['c', $a]     , 'Tag'      , ['getContent' , 'getAttributes', 'getTag' => 'p']],
            ['span'     , ['c', $a]     , 'Tag'      , ['getContent' , 'getAttributes', 'getTag' => 'span']],
            ['input'    , ['n', $a]     , 'Input'    , ['getName', 'getAttributes' => $ai]],
            ['password' , ['n', $a]     , 'Password' , ['getName', 'getAttributes' => $ap]],
            ['hidden'   , ['n', $a]     , 'Hidden'   , ['getName', 'getAttributes' => $ah]],
            ['textarea' , ['n', $a]     , 'Textarea' , ['getName', 'getAttributes' => $at]],
            ['checkbox' , ['n', $a]     , 'Checkbox' , ['getName', 'getAttributes' => $ac]],
            ['radio'    , ['n', $a]     , 'Radio'    , ['getName', 'getAttributes' => $ar]],
            ['button'   , ['l', 'n', $a], 'Button'   , ['getLabel', 'getName', 'getAttributes' => $ab]],
            ['submit'   , ['l', 'n', $a], 'Submit'   , ['getLabel', 'getName', 'getAttributes' => $as]],
            ['reset'    , ['l', 'n', $a], 'Reset'    , ['getLabel', 'getName', 'getAttributes' => $az]],
            ['select'   , ['n', $a]     , 'Select'   , ['getName', 'getAttributes']],
            ['checklist', ['a', $a]     , 'checklist', ['getName', 'getAttributes']],
//          ['fieldset' , ['a']         , 'Fieldset' , ['getName']],
            ['table'    , ['a']         , 'Table'    , ['getName']],
            ['div'      , ['n', $a]     , 'Div'      , ['getName' , 'getAttributes']],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testFactoryMethods($method, $args, $class, $tests)
    {
        $form = $this->getForm();

        for ($nb = 0; $nb <= count($args); $nb++) {
            $currentArgs = array_slice($args, 0, $nb, true);
            $currentTests = array_slice($tests, 0, $nb, true);

            // Appelle la méthode avec $nb parameters
            $item = call_user_func_array([$form, $method], $currentArgs); /** @var Item $item */

            // Vérifie que l'objet obtenu est du bon type
            $this->assertInstanceOf('Docalist\Forms\\' . $class, $item);

            // Vérifie que le type docalist hérite bien de Item
            $this->assertInstanceOf('Docalist\Forms\Item', $item);

            // Vérifie que l'objet a été ajouté au container
            $this->assertSame($form, $item->getParent());
            $this->assertTrue($form->has($item));

            // Vérifie que les accesseurs retournent le bon résultat
            $i = 0;
            foreach ($currentTests as $test => $result) {
                if (is_int($test)) {
                    $test = $result;
                    $result = $currentArgs[$i];
                }
                $this->assertSame($result, $item->$test());
                ++$i;
            }
        }
    }
}
