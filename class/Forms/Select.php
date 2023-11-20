<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
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
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class Select extends Choice
{
    /**
     * {@inheritdoc}
     */
    public const CSS_CLASS = 'select';

    /**
     * Code et libellé de la première option du select ou false pour désactiver le placeholder.
     *
     * @var array<string,string>|false
     */
    protected $firstOption = ['' => '…'];

    /**
     * Modifie le code et le libellé de la première option du select.
     *
     * Cette option est utilisée pour les select simples, elle est ignorée pour les select multiples.
     *
     * @param bool|string|array<string,string> $firstOption optionnel, valeur de l'option
     *
     * @throws InvalidArgumentException si $firstOption ets invalide
     */
    final public function setFirstOption(bool|string|array $firstOption = true): static
    {
        switch (true) {
            case false === $firstOption:
                $this->firstOption = false;
                return $this;
            case true === $firstOption:
                $this->firstOption = ['' => '…'];
                return $this;
            case is_string($firstOption):
                $this->firstOption = ['' => $firstOption];
                return $this;
            case is_array($firstOption):
                if (1 === count($firstOption)) {
                    $this->firstOption = $firstOption;
                    return $this;
                }
        }

        throw $this->invalidArgument('%s: invalid firstOption, array must contain one item.');
    }

    /**
     * Retourne le code et le libellé de la première option du select ou false si la première option est
     * désactivée.
     *
     * @return array<int|string,string>|false
     */
    final public function getFirstOption()// : mixed
    {
        return $this->firstOption;
    }

    /**
     * {@inheritdoc}
     *
     * Si le select est multivalué (multiple=true), la méthode ajoute '[]' au nom du contrôle.
     */
    final protected function getControlName(): string
    {
        $name = parent::getControlName();
        if ('' !== $name && $this->hasAttribute('multiple')) {
            $name .= '[]';
        }

        return $name;
    }

    final protected function isMultivalued(): bool
    {
        return parent::isMultivalued() || $this->hasAttribute('multiple');
    }

    final protected function displayOptions(Theme $theme, array $selected): void
    {
        // Affiche l'option vide (firstOption) si elle est activée et que ce n'est pas un select multiple
        if (!$this->hasAttribute('multiple') && $option = $this->getFirstOption()) {
            $this->displayOption($theme, (string)key($option), current($option), false, false);
        }

        // Affiche les options disponibles
        parent::displayOptions($theme, $selected);
    }

    final protected function displayOption(
        Theme $theme,
        string $value,
        string $label,
        bool $selected,
        bool $invalid
    ): void {
        // Détermine les attributs de l'option
        $attributes = ['value' => $value];
        if ($selected) {
            $attributes['selected'] = 'selected';
        }
        if ($invalid) {
            $class = static::CSS_CLASS.'-invalid-entry';
            // évite de cloner les options invalides
            if ($this->isRepeatable()) {
                $class .= ' do-not-clone';
            }
            $attributes['class'] = $class;
            $attributes['title'] = __('Option invalide', 'docalist-core');
            $label = __('Invalide : ', 'docalist-core').$label;
        }

        // Génère l'option
        $theme->tag('option', $attributes, $label);
    }

    final protected function startOptionGroup(string $label, Theme $theme): void
    {
        $theme->start('optgroup', ['label' => $label]);
    }

    final protected function endOptionGroup(Theme $theme): void
    {
        $theme->end('optgroup');
    }
}
