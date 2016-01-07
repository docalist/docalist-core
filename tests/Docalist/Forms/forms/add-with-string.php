<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Utilisation de add() avec une chaine')
     ->setDescription('Dans ce formulaire, les champs sont construits via des appels de la forme add(\'input\').');

$form->add('select', 'sex')->setLabel('Civilité')->setOptions(array('Mme', 'Mle', 'M.'));
$form->add('input', 'surname')->setLabel('Nom');
$form->add('input', 'firstname')->setLabel('Prénom');
$form->add('textarea', 'profile')->setLabel('Votre profil');

$form->add('submit', 'Go !');

return $form;
