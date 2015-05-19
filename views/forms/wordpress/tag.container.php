<?php
$writer->fullEndElement(); // </table>

$this->description() && (! $this->descriptionAfter) && $this->block('description');
$this->block('errors');
$this->block('values');
$this->description() && $this->descriptionAfter && $this->block('description');

$writer->startElement('table');
$writer->writeAttribute('class', 'form-table');
