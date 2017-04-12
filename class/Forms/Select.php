<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
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
 * Un menu déroulant de type select.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-select-element The select element}.
 */
class Select extends Choice
{
    /**
     * @var false|array Valeur et libellé de la première option du select.
     */
    protected $firstOption = ['' => '…'];

    /**
     * Modifie le libellé et la valeur de la première option du select.
     *
     * Cette option est utilisée pour les select simples, elle est ignorée pour les select multiples.
     *
     * @param string $firstOption Optionnel, valeur de l'option.
     *
     * @return self
     */
    public function setFirstOption($firstOption = true)
    {
        switch (true) {
            case $firstOption === false:
                break;
            case $firstOption === true:
                $firstOption = ['' => '…'];
                break;
            case is_string($firstOption):
                $firstOption = ['' => $firstOption];
                break;
            case is_array($firstOption):
                if (count($firstOption) !== 1) {
                    return $this->invalidArgument('%s: invalid firstOption, array must contain one item.');
                }
                break;
            default:
                $this->invalidArgument('%s: invalid firstOption, expected true, false, string or array.');
        }
        $this->firstOption = $firstOption;

        return $this;
    }

    /**
     * Retourne le libellé et la valeur de la première option du select ou false si la première option est
     * désactivée.
     *
     * @return false|array
     */
    public function getFirstOption()
    {
        return $this->firstOption;
    }

    /**
     * {@inheritdoc}
     *
     * Si le select est multivalué (multiple=true), la méthode ajoute '[]' au nom du contrôle.
     */
    protected function getControlName()
    {
        $name = parent::getControlName();
        $this->hasAttribute('multiple') && $name .= '[]';

        return $name;
    }

    protected function isMultivalued()
    {
        return parent::isMultivalued() || $this->hasAttribute('multiple');
    }

    protected function displayOptions(Theme $theme, array $options = [], array $data = [], array $attributes = [])
    {
        static $depth = 0;

        $visited = [];
        foreach ($options as $value => $label) {
            // Si label est un tableau c'est un optgroup : value contient le libellé du optgroup et label les options
            if (is_array($label)) {
                $depth && $this->invalidArgument('%s: options groups cannot be nested.');
                ++$depth;
                $theme->start('optgroup', ['label' => sprintf(__('%s :', 'docalist-core'), $value)]);
                $this->displayOptions($theme, $label, $data);
                foreach ($label as $value => $label) {
                    $visited[$value] = $value;
                }
                $theme->end('optgroup');
                --$depth;
                continue;
            }

            // Option normale
            $attr = $attributes + ['value' => $value];
            if (in_array($value, $data, false)) {
                $attr['selected'] = 'selected';
                $visited[$value] = $value;
            }

            $theme->tag('option', $attr, $label);
        }

        return array_diff_key(array_combine($data, $data), $visited);
    }
}
