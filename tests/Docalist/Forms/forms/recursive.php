<?php
use Docalist\Forms\Form;

$form = new Form();
$form->setLabel('Saisie des auteurs dans une table');

$form->input('test')->setLabel('test');

$f1 = $form->fieldset()->setLabel('fieldset A');
$f2 = $f1->fieldset()->setLabel('fieldset A1');
$f3 = $f2->fieldset()->setLabel('fieldset A11');
$     $f3->fieldset()->setLabel('fieldset A111')->input('inputA111')->setLabel('input A111');

$f2 = $f1->fieldset()->setLabel('fieldset A2');
$f3 = $f2->fieldset()->setLabel('fieldset A21');
      $f3->fieldset()->setLabel('fieldset A211')->checkbox('inputA211')->setLabel('input A211');

$f3 = $f2->fieldset()->setLabel('fieldset A22');
      $f3->fieldset()->setLabel('fieldset A221')->radio('inputA221')->setLabel('input A221');

$f1 = $form->fieldset()->setLabel('fieldset B');
$f2 = $f1->fieldset()->setLabel('fieldset B.1');
$f3 = $f2->fieldset()->setLabel('fieldset B.2');
      $f3->fieldset()->setLabel('fieldset B.3')->button('inputB.3')->setLabel('input B.3');

$form->submit('Go !');

return $form;
