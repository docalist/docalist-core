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

use Docalist\Forms\Table;
use Docalist\Forms\Theme;
use Docalist\Forms\Element;

/**
 * @var Table $this  Le container à afficher.
 * @var Theme $theme Le thème de formulaire en cours.
 * @var array $args  Paramètres transmis à la vue.
 */

// une table ne doit contenir que des éléments
// une table ne doit contenir que des éléments qui ont un layout

// Début de la table
$theme->start('table', ['class' => 'field-table'] + $this->getAttributes());

// Entête de la table
$theme->start('thead')->start('tr');
foreach ($this->getItems() as $item) { /** @var Element $item */
    $theme->start('th', ['class' => $item->getAttribute('class'), 'title' => $item->getDescription()]);
    $item->hasLabelBlock() && $theme->display($item, '_label');
    $theme->end('th');
}
$theme->end('tr')->end('thead');

// Remarque : pas d'attribut scope="col" dans l'entête
// For simple tables that have the headers in the first row or column then
// it is sufficient to simply use the TH elements without scope.
// source : http://www.w3.org/TR/WCAG20-TECHS/H63.html

// Corps de la table
$theme->start('tbody');
foreach (array_keys($this->getOccurences()) as $key) {
    $this->setOccurence($key);

    $theme->start('tr');
    foreach ($this->getItems() as $item) {
        $theme->start('td');
        $theme->display($item);
        $theme->end('td');
    }
    $theme->end('tr');
}
$theme->start('tbody');

// Fin de la table
$theme->end('table');
$this->isRepeatable() && $theme->display($this, '_add', ['data-clone' => '<tbody>tr:last-child']);
