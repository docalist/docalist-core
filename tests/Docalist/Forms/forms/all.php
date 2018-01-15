<?php
use Docalist\Forms\Form;

$repeat = true;

$form = new Form();
$form->setLabel('Un formulaire avec tous les types de champs');

$form->button()
     ->setLabel('button')
     ->setRepeatable($repeat);

$form->checkbox('checkbox')
     ->setLabel('checkbox')
     ->setRepeatable($repeat);

$form->checklist('checklist')
     ->setLabel('checklist')
     ->setRepeatable($repeat)
     ->setOptions(array(
        'un',
        'deux'
     ));

$form->fieldset()->setLabel('un fieldset')
     ->setRepeatable($repeat);

$form->hidden('hidden')
     ->setLabel('hidden')
     ->setRepeatable($repeat);

$form->input('input')
     ->setLabel('input')
     ->setRepeatable($repeat);

$form->password('password')
     ->setLabel('password')
     ->setRepeatable($repeat);

$form->radio('radio')
     ->setLabel('radio')
     ->setRepeatable($repeat);
/*
$form->radiolist('radiolist')
     ->setLabel('radio')
     ->setRepeatable($repeat)
     ->setOptions(array(
        'un',
        'deux'
     ));
*/
$form->reset()
     ->setLabel('reset')
     ->setRepeatable($repeat);

$form->select('select')
     ->setLabel('select')
     ->setRepeatable($repeat);

$form->submit()
     ->setLabel('submit')
     ->setRepeatable($repeat);

$form->table('table')
     ->setLabel('table')
     ->setRepeatable($repeat);

$form->tag('p', 'tag p')
//     ->setRepeatable($repeat)
;

$form->textarea('textarea')
     ->setLabel('textarea')
     ->setRepeatable($repeat);

$form->submit('Go !');

return $form;
