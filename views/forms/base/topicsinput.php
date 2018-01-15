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

use Docalist\Forms\TopicsInput;
use Docalist\Forms\Theme;
use Docalist\Table\TableInterface;
use Docalist\Forms\Hidden;
use Docalist\Forms\EntryPicker;
use Docalist\Forms\Tag;

/**
 * @var TopicsInput $this  L'élément de formulaire à afficher.
 * @var Theme       $theme Le thème de formulaire en cours.
 * @var array       $args  Paramètres transmis à la vue.
 */

// Récupère les données du champ et indexe par type
$data = [];
if ($this->data) {
    foreach($this->getData() as $topic) { /** @var Topic $topic */
        $type = isset($topic['type']) ? $topic['type'] : '';
        $term = isset($topic['value']) ? $topic['value'] : [];
        $data[$type] = $term;
    }
}

// Récupère la table qui contient la liste des vocabulaires
$table = $this->getTable();

// Le nom complet de la table est de la forme type:table
list($type, $tableName) = explode(':', $table);

// Ouvre la table
$table = docalist('table-manager')->get($tableName); /** @var TableInterface $table */

$theme->start('table', ['class' => 'form-table']);
$i = 0;
foreach($table->search() as $code => $topic) {
    list($type) = explode(':', $topic->source);
    $name = $this->getControlName() . '[' . $i . ']';

    $theme->start('tr');

    $theme->start('th');
        $theme->tag('label', [], $topic->label);
    $theme->end('th');

    $theme->start('td');
        $hidden = (new Hidden($name . '[type]'))->bind($code);
        $lookup = (new EntryPicker($name . '[value]'))
            ->setOptions($topic->source)
            ->setAttribute('multiple')
            ->setDescription($topic->description);
        if (isset($data[$code])) {
            $lookup->bind($data[$code]);
            unset($data[$code]);
        }
        $theme->display($hidden)->display($lookup);
    $theme->end('td');

    $theme->end('tr');
    ++$i;
}

foreach($data as $code => $terms) {
    $name = $this->getControlName() . '[' . $i . ']';

    $theme->start('tr');

    $theme->start('th');
        $theme->tag('label', ['style' => 'color: red'], $code);
    $theme->end('th');

    $theme->start('td');
        $hidden = (new Hidden($name . '[type]'))->bind($code);
        $lookup = (new EntryPicker($name . '[value]'))
            ->setOptions('index:' . $this->getName() . '.' . $code)
            ->setAttribute('multiple')
            ->bind($terms);
        $tag = (new Tag('p.description', "Le type de topic '$code' ne figure pas dans la table $tableName."));
        $theme->display($hidden)->display($lookup)->display($tag);
    $theme->end('td');

    $theme->end('tr');
    ++$i;
}

$theme->end('table');
