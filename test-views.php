#!/usr/bin/env php
<?php

$files = glob(__DIR__.'/storage/framework/views/*.php');
$errors = [];
foreach ($files as $file) {
    $result = shell_exec('php -l '.escapeshellarg($file).' 2>&1');
    if (strpos($result, 'error') !== false) {
        $errors[] = $file.': '.trim($result);
    }
}
if (empty($errors)) {
    echo "[✓] All views compile successfully\n";
    exit(0);
} else {
    echo "[✗] Found errors:\n";
    foreach ($errors as $error) {
        echo $error."\n";
    }
    exit(1);
}
