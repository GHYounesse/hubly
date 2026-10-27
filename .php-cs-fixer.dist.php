<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/migrations'])
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => false,
        'phpdoc_summary' => false,
        'yoda_style' => false,
        'single_line_throw' => false,
    ])
    ->setFinder($finder);
