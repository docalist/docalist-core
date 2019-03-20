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

namespace Docalist\Forms;

use InvalidArgumentException;

/**
 * Un menu déroulant de type select.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-select-element The select element}.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Select extends Choice
{
    /**
     * {@inheritdoc}
     */
    const CSS_CLASS = 'select';

    /**
     * Code et libellé de la première option du select ou false pour désactiver le placeholder.
     *
     * @var boolean|array
     */
    protected $firstOption = ['' => '…'];

    /**
     * Modifie le code et le libellé de la première option du select.
     *
     * Cette option est utilisée pour les select simples, elle est ignorée pour les select multiples.
     *
     * @param boolean|string|array $firstOption Optionnel, valeur de l'option.
     *
     * @throws InvalidArgumentException Si $firstOption ets invalide.
     *
     * @return self
     */
    public function setFirstOption($firstOption = true): self
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
     * Retourne le code et le libellé de la première option du select ou false si la première option est
     * désactivée.
     *
     * @return boolean|array
     */
    public function getFirstOption()// : mixed
    {
        return $this->firstOption;
    }

    /**
     * {@inheritdoc}
     *
     * Si le select est multivalué (multiple=true), la méthode ajoute '[]' au nom du contrôle.
     */
    protected function getControlName(): string
    {
        $name = parent::getControlName();
        $this->hasAttribute('multiple') && $name .= '[]';

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    protected function isMultivalued(): bool
    {
        return parent::isMultivalued() || $this->hasAttribute('multiple');
    }

    /**
     * {@inheritdoc}
     */
    protected function displayOptions(Theme $theme, array $selected): void
    {
        // Affiche l'option vide (firstOption) si elle est activée et que ce n'est pas un select multiple
        if (! $this->hasAttribute('multiple') && $option = $this->getFirstOption()) {
            $this->displayOption($theme, key($option), current($option), false, false);
        }

        // Affiche les options disponibles
        parent::displayOptions($theme, $selected);
    }

    /**
     * {@inheritdoc}
     */
    protected function displayOption(Theme $theme, string $value, string $label, bool $selected, bool $invalid): void
    {
        // Détermine les attributs de l'option
        $attributes = ['value' => $value];
        $selected && $attributes['selected'] = 'selected';
        if ($invalid) {
            $class = static::CSS_CLASS . '-invalid-entry';
            $this->isRepeatable() && $class .= ' do-not-clone'; // évite de clone les options invalides
            $attributes['class'] = $class;
            $attributes['title'] = __('Option invalide', 'docalist-core');
            $label = __('Invalide : ', 'docalist-core') . $label;
        }

        // Génère l'option
        $theme->tag('option', $attributes, $label);
    }

    /**
     * {@inheritdoc}
     */
    protected function startOptionGroup(string $label, Theme $theme): void
    {
        $theme->start('optgroup', ['label' => $label]);
    }

    /**
     * {@inheritdoc}
     */
    protected function endOptionGroup(Theme $theme): void
    {
        $theme->end('optgroup');
    }
}
