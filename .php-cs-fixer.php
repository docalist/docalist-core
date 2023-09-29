<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => false]
    ])
;
