<?php
$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests'])
    ->exclude(['vendor', 'var', 'stubs']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
