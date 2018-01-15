<?php
use Docalist\Forms\Form;

// reproduction du formulaire affiché en haut de la page
// http://reactiveraven.github.com/jqBootstrapValidation/

$form = new Form();
$form->setLabel('Into this')
     ->setDescription(
         'Reproduction du formulaire affiché en haut de ' .
         '<a href="http://reactiveraven.github.com/jqBootstrapValidation/">cette page</a>',
         false
     );

$form->input('email')
     ->setLabel('Email address')
     ->setDescription('Email address we can contact you on');

$form->input('emailAgain')
     ->setLabel('Email again')
     ->setDescription('And again, to check for speeling miskates');

$form->checklist('terms-and-conditions')
     ->setLabel('Legal')
     ->setOptions(array(
        'on' => 'I agree to the <a href="#">terms and conditions</a>'
     ));

$form->checklist('qualityControl')
     ->setLabel('Quality Control')
     ->setOptions(array(
        'fast' => 'Fast',
        'cheap' => 'Cheap',
        'good' => 'Good',
     ));

$form->submit('Test Validation')
     ->addClass('btn-primary')
     ->setDescription('(go ahead, nothing is sent anywhere)');

return $form;
