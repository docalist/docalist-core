<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Ecrivez-moi !')->setDescription('Utilisez le formulaire ci-dessous pour nous adresser un message.');

$form->select('civilite')->setLabel('Civilité :')->setOptions(array(
    'Mme',
    'Mle',
    'M.' => 'Monsieur'
));
$form->input('surname')->setLabel('Nom : ');
$form->input('firstname')->setLabel('Prénom : ');
$form->textarea('message')->setLabel('Votre message : ');

$form->submit('Go !');

return $form;
