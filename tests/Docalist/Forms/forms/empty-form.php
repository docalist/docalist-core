<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Un formulaire tout simple')->setDescription('Deux inputs text et un bouton submit.');
$form->input('surname')->setLabel('Nom : ');
/*
$form->input('firstname')->setLabel('Prénom : ');
$form->textarea('message')->setLabel('Votre message : ');
$form->select('m')->setLabel('Civilité :')->setOptions(array(
    'Mme',
    'Mle',
    'M' => 'Monsieur'
));

$form->select('n')->setLabel('Couleurs :')->multiple(true)->setOptions(array(
    'sombres' => array(
        'noir',
        'gris',
        'marron'
    ),
    'claires' => array(
        'blanc',
        'j'=>'jaune',
        'orange'
    ),
));
*/
$form->submit('Go !');

return $form;
