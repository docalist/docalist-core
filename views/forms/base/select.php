<?php declare(strict_types=1);
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
namespace Docalist\Views\Forms;

use Docalist\Forms\Select;
use Docalist\Forms\Theme;

/**
 * @var Select $this  L'élément de formulaire à afficher.
 * @var Theme  $theme Le thème de formulaire en cours.
 * @var array  $args  Paramètres transmis à la vue.
 */
$this->addClass($this::CSS_CLASS);
foreach ($this->getOccurences() as $key => $data) {
    // Définit l'occurence en cours
    $this->setOccurence($key);

    // Génère le début du Select
    $theme->start('select', ['name' => $this->getControlName()] + $this->getAttributes());

    // Affiche les options disponibles
    $this->displayOptions($theme, (array) $data);

    // Génère la fin du Select
    $theme->end('select');
}
$this->isRepeatable() && $theme->display($this, '_add');
