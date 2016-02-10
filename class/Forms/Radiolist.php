<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Forms;

/**
 * Une liste de boutons radio.
 */
class Radiolist extends Choice
{
    /**
     * Une radiolist est représentée par un <ul>, le libellé associé ne doit pas avoir
     * d'attribut 'for' associé car un ul n'est pas labelable.
     *
     * @return false
     */
    protected function isLabelable()
    {
        return false;
    }

    protected function displayOptions(Theme $theme, array $options = [], array $data = [], array $attributes = [])
    {
        $visited = [];
        foreach ($options as $value => $label) {
            // Si label est un tableau c'est un optgroup : value contient le libellé du optgroup et label les options
            if (is_array($label)) {
                $theme
                    ->start('li', ['class' => 'optgroup'])
                    ->tag('p', ['class' => 'optgroup-label'], sprintf(__('%s :', 'docalist-core'), $value))
                    ->start('ul');
                $this->displayOptions($theme, $label, $data);
                foreach($label as $value => $label) {
                    $visited[$value] = $value;
                }
                $theme->end('ul')->end('li');
                continue;
            }

            // Option normale
            $attr = ['name' => $this->getControlName(), 'type' => 'radio', 'value' => $value];
            if (in_array($value, $data, false)) {
                $attr['checked'] = 'checked';
                $visited[$value] = $value;
            }

            $theme
                ->start('li')
                    ->start('label', $attributes)
                        ->tag('input', $attr)->html($label)
                    ->end('label')
                ->end('li');
        }

        return array_diff_key(array_combine($data, $data), $visited);
    }
}
