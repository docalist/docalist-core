<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\Checkbox;
use Docalist\Forms\Theme;

/**
 * @var Checkbox $this  L'élément de formulaire à afficher.
 * @var Theme    $theme Le thème de formulaire en cours.
 * @var array    $args  Paramètres transmis à la vue.
 */
$description = $this->getDescription();
foreach($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);

    // Si la checknox a une description, on l'utilise comme libellé supplémentaire
    $description && $theme->start('label');

    // Génère un input hidden qui sert à transmettre '' lorsque la case est décochée
    $theme->tag('input', ['type' => 'hidden', 'name' => $this->getControlName()]);

    // Génère la checkbox
    $theme->tag('input', ['name' => $this->getControlName(), 'checked' => (bool)$data] + $this->getAttributes());

    // Ferme le label
    $description && $theme->html($description)->end('label');
}
$this->isRepeatable() && $theme->display($this, '_add');
