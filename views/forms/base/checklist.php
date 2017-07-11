<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Views
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Views\Forms; /* default */

use Docalist\Forms\Checklist;
use Docalist\Forms\Theme;

/**
 * @var Checklist $this  L'élément de formulaire à afficher.
 * @var Theme     $theme Le thème de formulaire en cours.
 * @var array     $args  Paramètres transmis à la vue.
 */
$options = $this->loadOptions();
foreach($this->getOccurences() as $key => $data) {
    $this->setOccurence($key);

    // Début de la checklist
    $this->addClass('checklist');
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

    // Fin de la checklist
    $theme->end('ul');
}
$this->isRepeatable() && $theme->display($this, '_add');
