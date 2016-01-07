<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Saisie des auteurs dans une table')
     ->setDescription('Indiquez les auteurs du document en séparant les auteurs physiques (personnes) des auteurs moraux (organismes).');

$form->input('test')->setLabel('test');

$author = $form->table('author')
    ->setRepeatable(true)
    ->setLabel('Personnes')
    ->setDescription('Indiquez les personnes auteurs du document.');

$author->input('surname')->setLabel('Nom');
$author->input('firstname')->setLabel('Prénom')->setRepeatable(true);
$author->select('role')->setLabel('Rôle')->setOptions(array(
    'trad.',
    'pref.',
));

$org = $form->table('organisation')
    ->setRepeatable(true)
    ->setLabel('Organismes')
    ->setDescription('Indiquez les organismes auteurs du document.');

$org->input('name')->setLabel('Nom');
$org->input('city')->setLabel('Ville');
$org->input('country')->setLabel('Pays');
$org->select('role')->setLabel('Rôle')->setOptions(array(
    'com.',
    'financ.',
));

$form->submit('Go !');

$form->bind(array(
    'test' => 'test',
    'author' => array(
        array(
            'surname' => 'Ménard',
            'firstname' => array(
                'Daniel',
                'Etienne',
                'Louis',
            ),
        ),
        array(
            'surname' => 'Goron',
            'firstname' => array(
                'Gaëlle',
                'Solange',
            ),
        ),
    ),
    'organisation' => array(
        array(
            'name' => 'docalist',
            'city' => 'Saint-Gilles',
            'country' => 'fra',
        ),
    ),
));

return $form;
