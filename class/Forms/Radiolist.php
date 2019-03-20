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

/**
 * Une liste de boutons radio.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Radiolist extends Choice
{
    /**
     * La valeur de l'attribut 'type' dans le tag input généré par displayOption().
     *
     * @var string
     */
    const INPUT_TYPE = 'radio';

    /**
     * {@inheritdoc}
     */
    const CSS_CLASS = 'radiolist';

    /**
     * Une checklist ou une radiolist sont représentés par des <ul>, le libellé associé ne doit pas
     * avoir d'attribut 'for' associé car un ul n'est pas labelable.
     *
     * @return boolean
     */
    protected function isLabelable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayOption(Theme $theme, string $value, string $label, bool $selected, bool $invalid): void
    {
        // Détermine les attributs de l'option
        $attributes = [];
        if ($invalid) {
            $class = static::CSS_CLASS . '-invalid-entry';
            $this->isRepeatable() && $class .= ' do-not-clone'; // évite de cloner les options invalides
            $attributes['class'] = $class;
            $attributes['title'] = __('Option invalide', 'docalist-core');
        }

        // Début du <li>
        $theme->start('li', $attributes);

        // Début du <label>
        $theme->start('label');

        // Case à cocher
        $attributes = ['name' => $this->getControlName(), 'type' => static::INPUT_TYPE, 'value' => $value];
        $selected && $attributes['checked'] = 'checked';
        $theme->tag('input', $attributes)->html(' ' . $label);

        // Fin du <label>
        $theme->end('label');

        // Fin du <li>
        $theme->end('li');
    }

    /**
     * {@inheritdoc}
     */
    protected function startOptionGroup(string $label, Theme $theme): void
    {
        // Début du <li>
        $theme->start('li', ['class' => static::CSS_CLASS . '-group']);

        // Libellé du groupe
        $theme->tag('p', ['class' => static::CSS_CLASS . '-group-label'], $label);

        // Début des options du groupe
        $theme->start('ul');
    }

    /**
     * {@inheritdoc}
     */
    protected function endOptionGroup(Theme $theme): void
    {
        // Fin des options du groupe
        $theme->end('ul');

        // Fin du <li>
        $theme->end('li');
    }
}
