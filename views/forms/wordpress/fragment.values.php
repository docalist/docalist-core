<?php
$writer->startElement('table');
//$writer->writeAttribute('border', '1');
$writer->writeAttribute('class', 'form-table');

$this->parentblock();
$writer->fullEndElement(); // </table>
