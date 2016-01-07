<?php
use Docalist\Forms\Checklist;

$ctl = new Checklist('simple');

$ctl->setLabel('Libellé de la checklist')
    ->setOptions(array('value1'=>'option 1', 'value2' => 'option 2'))
    ->setDescription('Description de la checklist.');

return $ctl;
