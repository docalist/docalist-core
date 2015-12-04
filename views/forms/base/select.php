<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Views
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms; /* default */

use Docalist\Forms\Select;
use Docalist\Forms\Theme;

/**
 * @var Select $this  L'élément de formulaire à afficher.
 * @var Theme  $theme Le thème de formulaire en cours.
 * @var array  $args  Paramètres transmis à la vue.
 */
$options = $this->loadOptions();
foreach($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);

    // Début du Select
    $theme->start('select', ['name' => $this->getControlName()] + $this->getAttributes());

    // Affiche l'option vide (firstOption) si elle est activée et que ce n'est pas un select multiple
    if (! $this->hasAttribute('multiple') && $option = $this->getFirstOption()) {
        $this->displayOptions($theme, $option);
    }

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

    // Fin du Select
    $theme->end('select');
}
$this->isRepeatable() && $theme->display($this, '_add');