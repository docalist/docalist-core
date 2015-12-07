<?php
use Docalist\Forms\Form;

// reproduction du formulaire affiché en haut de la page
// http://reactiveraven.github.com/jqBootstrapValidation/

$form = new Form();
$form->setLabel('Connexion au site')
     ->setDescription('Un formulaire de connexion', false);

$form->input('login')
     ->setLabel('Login')
     ->setAttribute('placeholder', 'Indiquez votre nom d\'utilisateur');

$form->password('password')
     ->setLabel('Mot de passe')
     ->setAttribute('placeholder', 'Votre mot de passe')
     ->setDescription('<a href="#">J\'ai oublié mon mot de passe...</a>');

$form->checkbox('rememberme')
     ->setLabel('Se souvenir de moi');

$form->submit('Connexion')
     ->addClass('btn-primary');

$form->submit('Annuler')
     ->addClass('');

return $form;