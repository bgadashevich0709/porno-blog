<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'public']) // Исключаем кэш, вендор и фронтенд
; // Исключаем отдельные файлы, если нужно

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true, // Самый актуальный стандарт кодинга
        '@PHP84Migration' => true, // Поддержка синтаксиса PHP 8.4
        'array_syntax' => ['syntax' => 'short'], // Короткие массивы []
        'ordered_imports' => ['sort_algorithm' => 'alpha'], // Сортировка юзов по алфавиту
        'no_unused_imports' => true, // Удаление неиспользуемых use
        'strict_param' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
