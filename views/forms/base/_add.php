<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Element;
use Docalist\Forms\Theme;

/**
 * @var Element $this  L'élément de formulaire à afficher.
 * @var Theme   $theme Le thème de formulaire en cours.
 * @var array   $args  Paramètres transmis à la vue.
 */

// Si l'item n'est pas répétable, terminé
if (! $this->isRepeatable()) {
    $theme->text('View _add: ' . ($this->getName() ?: $this->getType()) . '::isRepeatable() is false.');

    return;
}

// Récupère le libellé du champ ou son nom s'il n'a pas de label
$label = $this->getLabel() ?: $this->getName();

// Un libellé contenant '-' signifie 'ne pas afficher de libellé'
if (empty($label) || $label === '-') {
    return;
}

// Insère un espace (non significatif) avant le bouton
// pour éviter qu'il ne "colle" au contrôle qui précède
$theme->text(' ');

// Détermine les attributs du bouton
$attributes = ['type' => 'button', 'class' => 'cloner button button-link'];
$args; // évite warning eclipse
if (isset($args['data-clone']) && $args['data-clone'] !== '<') {
    $attributes['data-clone'] = $args['data-clone'];
}

$level = $this->getRepeatLevel();
$level > 1 && $attributes['data-level'] = $level;

// Détermine le libellé du bouton
if (isset($args['content'])) {
    $content = $args['content'];
} else {
    $content = '<span class="dashicons-before dashicons-plus-alt">';
    $label = $this->getLabel();
    $label && $label !== '-' && $content .= $label;
    $content .= '</span>';
}

// Génère le bouton
$theme->tag('button', $attributes, $content);
