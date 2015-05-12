<?php
/**
 * Ce template sert à parcourir toutes les options d'un Choice et à faire
 * l'aiguillage entre les options simples et les groupes d'option.
 */

// Détermine les valeurs actuellement sélectionnées
if ($this->data instanceof Docalist\Type\Collection) {
    // par exemple si on a passé un objet "Settings" ou Property comme valeur actuelle du champ
    $selected = array_flip($this->data->value());
} else {
    $selected = array_flip((array)$this->data);
}

// Teste si des clés ont été fournies (i.e. autre chose qu'un tableau numérique séquentiel)
$hasKeys = array_keys($this->options) !== range(0, count($this->options) - 1);

foreach ($this->options as $value => $label) {
    // Groupe d'options
    if (is_array($label)) {
        $this->block('optgroup', array(
            'label' => $value,
            'options' => $label,
            'selected' => $selected,
        ));
    }

    // Option simple
    else {
       $key = $hasKeys ? $value : $label;
        if ($flag = isset($selected[$key])) {
            unset($selected[$key]);
        }
        $this->block('option', array(
            'value' => $hasKeys ? $value : null,
            'label' => $label,
            'selected' => $flag,
        ));
    }
}

return array_keys($selected);
