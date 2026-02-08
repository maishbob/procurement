<?php

$file = __DIR__ . '/resources/views/suppliers/create.blade.php';
$content = file_get_contents($file);

// Fix the broken escaped arrow
$content = str_replace('?\->', '?->', $content);
$content = str_replace('-\->', '-->', $content);

file_put_contents($file, $content);

echo "Fixed escaped arrows in supplier form\n";
