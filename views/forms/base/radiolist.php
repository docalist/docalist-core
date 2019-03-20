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

namespace Docalist\Views\Forms;

use Docalist\Forms\Radiolist;
use Docalist\Forms\Theme;

/**
 * @var Radiolist $this  L'élément de formulaire à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
$this->addClass($this::CSS_CLASS);
foreach ($this->getOccurences() as $key => $data) {
    // Définit l'occurence en cours
    $this->setOccurence($key);

    // Génère le début de la radiolist
    $theme->start('ul', $this->getAttributes());

    // Affiche les options disponibles
    $this->displayOptions($theme, (array) $data);

    // Génère la fin de la radiolist
    $theme->end('ul');
}
$this->isRepeatable() && $theme->display($this, '_add');
