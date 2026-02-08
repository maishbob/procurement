<?php

$file = __DIR__ . '/resources/views/suppliers/create.blade.php';
$content = file_get_contents($file);

// Fix any messed up replacements
$content = str_replace('$supplier\->', '$supplier?->', $content);
$content = str_replace('<!-- Trading Name -\->', '<!-- Trading Name -->', $content);
$content = str_replace('<!-- Registration Number -\->', '<!-- Registration Number -->', $content);

// Make sure all $supplier-> are converted (in case some were missed)
$content = str_replace('$supplier->', '$supplier?->', $content);

file_put_contents($file, $content);

echo "Fixed supplier null-safe operators\n";
