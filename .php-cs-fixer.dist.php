<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR1' => true,
        '@PSR12' => true
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache');
