<?php
// vue de test pour ViewsTest.php
use Docalist\Tests\ViewsTest;

/**
 * Affiche un message d'information.
 */
/* @var ViewsTest $this */
return [
    $this->publicMethod(),
    $this->protectedMethod(),
    $this->privateMethod(),
];
