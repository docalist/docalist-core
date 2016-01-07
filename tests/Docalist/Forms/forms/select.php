<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Formulaire de test pour les select');
$t = array('Multiple=false' => false, 'Multiple=true' => true);

foreach ($t as $title=>$multiple) {

    $form->tag('h4', $title);

    $form->select('m')->setLabel('que des options, pas de value :')->setAttribute('multiple', $multiple)->setOptions(array(
        'Mme',
        'Mle',
        'Monsieur'
    ));

    $form->select('m')->setLabel('que des options, monsieur a une value :')->setAttribute('multiple', $multiple)->setOptions(array(
        'Mme',
        'Mle',
        'M' => 'Monsieur'
    ));

    $form->select('n')->setLabel('que des optgroup, value pour jaune')->setAttribute('multiple', $multiple)->setOptions(array(
        'sombres' => array(
            'noir',
            'gris',
            'marron'
        ),
        'claires' => array(
            'blanc',
            'J'=>'jaune',
            'orange'
        ),
    ));

    $form->select('n')->setLabel('trois options puis un optgroup puis deux options')->setAttribute('multiple', $multiple)->setOptions(array(
        'noir',
        'gris',
        'marron',
        'claires' => array(
            'blanc',
            'j'=>'jaune',
            'orange'
        ),
        'bleu',
        'V' => 'vert',
    ));
}
$form->submit('Go !');

return $form;
