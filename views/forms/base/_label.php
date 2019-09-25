<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Element;
use Docalist\Forms\Theme;

/**
 * @var Element $this  L'élément de formulaire à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */

// Si l'item ne veut pas de bloc 'label', terminé
if (! $this->hasLabelBlock()) {
    $theme->text('View _label: ' . ($this->getName() ?: $this->getType()) . '::hasLabelBlock() is false.');

    return;
}

// Récupère le libellé du champ ou son nom s'il n'a pas de label
$label = $this->getLabel() ?: $this->getName();

// Un libellé contenant '-' signifie 'ne pas afficher de libellé'
if (empty($label) || $label === '-') {
    return;
}

// Détermine les attributs du tag <label>
$attributes = ($this->isLabelable() && ! $this->isRepeatable()) ? ['for' => $this->generateID()] : [];

// Ouvre le tag <label>
$theme->start('label', $attributes);

// génère le libellé
$theme->html($label);

// Génère une astérisque si le champ est obligatoire
if (!empty($this->getRequired())) {
    $theme->tag('span', ['class' => 'screen-reader-text'], __(' (requis)', 'docalist-core'));
}

// Ferme le tag <label>
$theme->end('label');