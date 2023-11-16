<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => false,
        'single_line_throw' => false,
        'phpdoc_annotation_without_dot' => false,
        'yoda_style' => false,
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
