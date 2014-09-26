<?php
// Détermine la table utilisée et les champs utilisés pour code et label
$valueField = $this->valueField();
$labelField = $this->labelField();
$tableName = $this->table();
list($type, $name) = explode(':', $tableName);

// Récupère les données du champ
if ($this->data instanceof Docalist\Type\Collection) {
    // par exemple si on a passé un objet "Settings" ou Property comme valeur actuelle du champ
    $data = $this->data->value();
} else {
    $data = (array)$this->data;
}

$options = $data;

// Si le lookup porte sur une table, il faut convertir les codes en libellés
if (!empty($data) && ($type === 'table' || $type === 'thesaurus')) {

    // Ouvre la table
    $table = docalist('table-manager')->get($name);

    // Construit la clause WHERE ... IN (...)
    $options = [];
    foreach ($data as $option) {
        $options[]= $table->quote($option);
    }
    $where = $valueField . ' IN (' . implode(',', $options) . ')';

    // Recherche tous les articles, réponse de la forme code => label
    $results = $table->search("$valueField,$labelField", $where);

    // Construit le tableau d'options, en respectant l'ordre initial des articles
    $options = [];
    foreach($data as $key) {
        // article trouvé
        if (isset($results[$key])) {
            $options[$key] = $results[$key];
        }

        // article non trouvé
        else {
            $options[$key] = 'Invalide : ' . $key;
        }
    }
}

// 4. Initialise les options du Select
$this->options = $options;

// Garantit que le contrôle a un ID, pour y accèder dans le tag <script>
$this->generateId();

// Génère le select
$args['data-table'] = $tableName;
// important : depuis jquery 1.5 la casse des attributs data est changée
// - xY sera convertit en xy
// - x-y sera convertit en xY
// cf. http://stackoverflow.com/a/22753630
$valueField !== 'code' && $args['data-value-field'] = $valueField;
$labelField !== 'label' && $args['data-label-field'] = $labelField;

// Génère le script inline qui intialise selectize()
$this->parentBlock($args);
    $writer->startElement('script');
    $writer->writeAttribute('type', 'text/javascript'); // pas nécessaire en html5
    $writer->writeAttribute('class', 'do-not-clone'); // indique à deocalist-forms.js qu'il ne faut pas cloner cet élément

    $id = $this->attribute('id');
    $writer->writeRaw("jQuery('#$id').tableLookup();");
$writer->fullEndElement();