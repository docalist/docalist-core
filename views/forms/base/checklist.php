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
    $theme->start('ul', ['class' => 'checklist'] + $this->getAttributes());

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

// TODO : style ci-dessous à transférer dans css
?>
<style>
.checklist,.checklist li { margin: 0 }          /* Supprime toutes les marges dans les ul et les li des checklist */
.checklist .invalid-option { color: red }       /* affiche en rouge les options invalides */
.checklist .optgroup + li { margin-top: 0.5em } /* Insère une marge avant le premier li qui suit un optgroup */
.description + .checklist { margin-top: 0.5em;} /* Insère un espace entre la description et le ul.checklist */

.checklist.inline>li {vertical-align: top;}
.checklist.inline .optgroup + li { margin-top: 0 } /* Supprime la marge qu'on a ajouté au dessus pour un optgroup */
.checklist.inline>li+li {margin-left: 1em}
.checklist.inline .optgroup ul {margin-left: 0;}
</style>