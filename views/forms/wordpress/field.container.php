<?php
global $level;

++$level;
//$writer->text("level=$level\n");

if ($level === 1) {
    $this->block('values');
} else {
    $writer->startElement('tr');

    $writer->startElement('th');
    $writer->writeAttribute('scope', 'row');
    $writer->writeAttribute('valign', 'top');
    $this->label() && $this->block('label');
    $writer->fullEndElement(); // </th>

    $writer->startElement('td');
    $writer->writeAttribute('valign', 'top');
    $this->description() && (! $this->descriptionAfter) && $this->block('description');
    $this->block('errors');
    $this->block('values');
    $this->description() && $this->descriptionAfter && $this->block('description');
    $writer->fullEndElement(); // </td>

    $writer->fullEndElement(); // </tr>
}
--$level;