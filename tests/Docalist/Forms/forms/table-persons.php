<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Saisie des auteurs dans une table');

$form->input('test')->setLabel('test');

$author = $form->table('author')->setRepeatable(true)->setLabel('Personnes');
$author->input('surname')->setLabel('Nom');
$author->input('firstname')->setLabel('Prenom')->setRepeatable(true);
$author->select('role')->setLabel('Rôle')->setOptions(array(
    'trad.',
    'pref.',
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
    )
));

return $form;
