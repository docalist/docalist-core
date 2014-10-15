<?php
use Docalist\Table\TableInterface;
use Docalist\Forms\TableLookup;
use Docalist\Forms\Hidden;
use Docalist\Biblio\Field\Topic;
use Docalist\Forms\Input;
use Docalist\Forms\TopicsInput;

/* @var $this TopicsInput */

// Récupère les données du champ et indexe par type
$data = [];
if ($this->data) {
    $t = ($this->data instanceof Docalist\Type\Collection) ? $this->data->value() : (array)$this->data;
    foreach($t as $topic) { /* @var $topic Topic */
        $type = isset($topic['type']) ? $topic['type'] : '';
        $term = isset($topic['term']) ? $topic['term'] : [];
        $data[$type] = $term;
    }
}

// Récupère la table qui contient les vocabulaires
$table = $this->table;

// Le nom complet de la table est de la forme type:table
list($type, $tableName) = explode(':', $table);

// ouvre la table
$table = docalist('table-manager')->get($tableName); /* @var $table TableInterface */

// Avec le thème wordpress, quand on arrive là, on est dans un td.
// Le libellé du champ a déjà été affiché dans le th
// S'il y a une description, elle a également été affichée au début du td

// On va générer une table qui contient une ligne pour chacune des entrées
// présentes dans la table qui liste les vocabulaires disponibles.

// Pour "stocker" le type d'indexation, on utilise des champs hidden. Plutôt
// que de les afficher entre deux tr, on les stocke et on les affiche à la fin
// une fois qu'on aura fermé la table.
$writer->startElement('table');
$writer->writeAttribute('class', 'form-table');
$hidden = [];
$i = 0;
foreach($table->search() as $code => $topic) {
    list($type, $source) = explode(':', $topic->source);

    $name = $this->controlName() . '[' . $i . ']';

    $field = new Hidden($name . '[type]');
    $field->attribute('value', $code);
    $hidden[] = $field;

    $field = new TableLookup($name . '[term]', $topic->source);
    if ($type === 'index') {
        $field->labelField('text');
    }

    $field->multiple(true);
    $field->label($topic->label);
    $field->description($topic->description);
    if (isset($data[$code])) {
        $field->data($data[$code]);
        unset($data[$code]);
    }
    $field->block('container');

    $i++;
}

// Si la notice contient des mots-clés dont le type n'est pas déclaré dans
// la table des vocabulaires disponibles, on les affiche avec un style qui
// montre qu'il y a un problème.
$href = admin_url("options-general.php?page=docalist-tables&m=TableEdit&tableName=$tableName");
foreach($data as $code => $terms) {
    $name = $this->controlName() . '[' . $i . ']';

    $field = new Hidden($name . '[type]');
    $field->attribute('value', $code);
    $hidden[] = $field;

    empty($code) && $code = '(aucun code)';

    $field = new Input($name . '[term]');
    $field->repeatable(true);
    $field->repeatLevel(2);
    $field->label($code);
    $field->description("<b>Erreur :</b> cette notice contient des mots-clés de type <b>$code</b> qui ne figurent pas dans la table <a href='$href'>$tableName</a>.");
    $field->attribute('style', 'color:red');
    $field->data($terms);
    $field->block('container');

    $i++;
}
$writer->fullEndElement(); // /table

// Affiche les input.hidden qui indiquent le type de chaque topic
foreach($hidden as $field) {
    $field->block('widget');
}