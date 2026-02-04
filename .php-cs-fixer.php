<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)->exclude('tests');

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'declare_strict_types' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'phpdoc_to_comment' => false,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);