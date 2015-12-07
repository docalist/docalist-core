<?php
use Docalist\Forms\Form;

$repeat = true;
$desc = 'Description du champ';

$form = new Form();
$form->setLabel('Un formulaire avec tous les types de champs');

$pos = array(
    'Description affichée à sa position par défaut' => null,
    'Description affichée en haut (avant le champ)' => false,
    'Description affichée en bas (après le champ)' => true,
);

foreach($pos as $title => $pos) {
    $form->tag('h3', $title);

    $form->button()
         ->setLabel('button')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->checkbox('checkbox')
         ->setLabel('checkbox')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->checklist('checklist')
         ->setLabel('checklist')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat)
         ->setOptions(array(
            'un',
            'deux'
         ));

    $form->fieldset()->setLabel('un fieldset')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->hidden('hidden')
         ->setLabel('hidden')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->input('input')
         ->setLabel('input')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->password('password')
         ->setLabel('password')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->radio('radio')
         ->setLabel('radio')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);
    /*
    $form->radiolist('radiolist')
         ->setLabel('radio')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat)
         ->setOptions(array(
            'un',
            'deux'
         ));
    */
    $form->reset()
         ->setLabel('reset')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->select('select')
         ->setLabel('select')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->submit()
         ->setLabel('submit')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->table('table')
         ->setLabel('table')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

//     $form->tag('p')
//          ->setLabel('tag p')
//          ->setDescription($desc, $pos)
//          ->setRepeatable($repeat);

    $form->textarea('textarea')
         ->setLabel('textarea')
         ->setDescription($desc, $pos)
         ->setRepeatable($repeat);

    $form->submit('Go !');
}
return $form;