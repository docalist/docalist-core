<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true
        ],
        'no_superfluous_phpdoc_tags' => [
            'remove_inheritdoc' => false
        ],
        'binary_operator_spaces' => [
            'default'   => 'single_space',
            'operators' => [
                // '=' => 'align_single_space',
                //'=>' => 'align_single_space',
                '=>' => 'align_single_space_minimal_by_scope',
            ],
        ],
    ])
;
