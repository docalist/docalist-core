<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Champs répétables');

// -----------------------------------------------------------------------------

$form->table('un')->setLabel('Répétable niveau 1')->setRepeatable(true)
     ->table('deux')->setLabel('Répétable niveau 2')->setRepeatable(true)
     ->table('trois')->setLabel('Répétable niveau 3')->setRepeatable(true)
     ->table('quatre')->setLabel('Répétable niveau 4')->setRepeatable(true)
     ->table('cinq')->setLabel('Répétable niveau 5')->setRepeatable(true)
     ->input('data')->setRepeatable(true);

// -----------------------------------------------------------------------------
$form->submit('Go !');

return $form;
