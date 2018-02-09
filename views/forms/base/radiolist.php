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
namespace Docalist\Views\Forms; /* default */

use Docalist\Forms\Radiolist;
use Docalist\Forms\Theme;

/**
 * @var Radiolist $this  L'élément de formulaire à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
$options = $this->loadOptions();
foreach ($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);

    // Début de la radiolist
    $this->addClass('radiolist');
    $theme->start('ul', $this->getAttributes());

    // Affiche les options
    $badValues = $this->displayOptions($theme, $options, (array) $data);

    // Si data contient des options non autorisées, on les affiche en rouge
    if (! empty($badValues)) {
        $attributes = [
            'style' => 'color:red',
            'title' => "Cette valeur figure dans le champ mais ce n'est pas une entrée autorisée."
        ];
        $this->displayOptions($theme, $badValues, $badValues, $attributes);
    }

    // Fin de la radiolist
    $theme->end('ul');
}
$this->isRepeatable() && $theme->display($this, '_add');
