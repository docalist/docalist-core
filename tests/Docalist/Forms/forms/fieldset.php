<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Un formulaire contenant deux fieldsets')->setDescription('Chaque fieldset contient quelques champs.');

$form->input('i1')->setLabel('Hors fieldset');

$fieldset = $form->fieldset()->setLabel('Coordonnées');

$fieldset->select('m')->setLabel('Civilité :')->setOptions(array(
    'Mme',
    'Mle',
    'M' => 'Monsieur'
));
$fieldset->input('surname')->setLabel('Nom : ');
$fieldset->input('firstname')->setLabel('Prénom : ');
$fieldset->textarea('adresse')->setLabel('Votre message : ');

$form->input('i2')->setLabel('Hors fieldset');

$form->submit('Go !');

return $form;
