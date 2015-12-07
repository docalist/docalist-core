<?php
use Docalist\Forms\Form;

$colors = array(
    'Claires :' => array(
        'beige',
        'j' => 'jaune',
        'orange'
    ),
    'Sombres :' => array(
        'noir',
        'gris',
        'marron'
    ),
);

$form = new Form();
$form->setLabel('Test des checklist');

foreach(array(1=>false, 2=>true) as $i=>$repeat) {
    $form->checklist("empty$i")
         ->setLabel('Vide :')
         ->setDescription('Une checklist vide, aucune option n\'a été fournie.')
         ->setRepeatable($repeat);

    $form->checklist("clair$i")
         ->setLabel('Couleurs :')
         ->setDescription('Une checklist simple, trois options de base sans attribut "value".')
         ->setRepeatable($repeat)
         ->setOptions($colors['Sombres :']);

    $form->checklist("sombre$i")
         ->setLabel('Couleurs :')
         ->setDescription('Une checklist simple, un attribut value="j" a été indiqué pour la couleur jaune.')
         ->setRepeatable($repeat)
         ->setOptions($colors['Claires :']);

    $form->checklist("group$i")
         ->setLabel('Couleurs :')
         ->setDescription('Une checklist hiérarchique contenant des optgroup.')
         ->setRepeatable($repeat)
         ->setOptions($colors);

    $form->checklist("group$i")
         ->addClass('inline')
         ->setLabel('Couleurs :')
         ->setDescription('INLINEUne checklist hiérarchique contenant à la fois des options simples et des groupes.')
         ->setRepeatable($repeat)
         ->setOptions(array('transparent', 'blanc') + $colors + array('opaque'));

    if ($i === 1) {
        $form->tag('h3', 'Faisons maintenant la même chose mais en mettant l\'attribut repeatable à true :');
    }
}
$form->submit('Go !');

return $form;
