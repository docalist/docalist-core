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
namespace Docalist\Views\Forms\Base;

use Docalist\Forms\EntryPicker;
use Docalist\Forms\Theme;
use Docalist\Type\Collection;

/**
 * @var EntryPicker $this  Le champ à afficher.
 * @var Theme       $theme Le thème de formulaire en cours.
 * @var array       $args  Paramètres transmis à la vue.
 */

// Envoie le js et la CSS de selectize
$theme->enqueueStyle('selectize')->enqueueScript('selectize');

// Détermine la table utilisée et les champs utilisés pour code et label
// $valueField = $this->valueField();
// $labelField = $this->labelField();
// $tableName = $this->table();
//list($type, $name) = explode(':', $tableName);

// Récupère les données du champ
if ($this->data instanceof Collection) {
    // par exemple si on a passé un objet "Settings" ou Property comme valeur actuelle du champ
    $data = $this->data->getPhpValue();
} else {
    $data = (array)$this->data;
}

// Si les lookups portent sur une table, il faut convertir les codes en libellés
$options = $this->convertCodes($data);

// Garantit que le contrôle a un ID, pour y accèder dans le tag <script>
$id = $this->generateId();

// Détermine les attributs du select
list($type, $source) = explode(':', $this->getOptions(), 2);
$attributes = ['name' => $this->getControlName(), 'data-lookup-type' => $type, 'data-lookup-source' => $source];
// $valueField !== 'code' && $attributes['data-value-field'] = $valueField; // valueField/labelField en js
// $labelField !== 'label' && $attributes['data-label-field'] = $labelField; // (cf. http://stackoverflow.com/a/22753630)
$attributes += $this->getAttributes();

// Début du select
$theme->start('select', $attributes);

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

// Génère le script inline qui initialise selectize()
$theme->tag(
    'script',
    ['type' => 'text/javascript', 'class' => 'do-not-clone'],
    'jQuery("#' . $id . '").tableLookup();'
);
