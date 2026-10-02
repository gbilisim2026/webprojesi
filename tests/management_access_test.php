<?php

$requiredFiles = [
    __DIR__ . '/../admin_management.php',
    __DIR__ . '/../assistant_management.php',
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        fwrite(STDERR, "Missing management page: {$file}\n");
        exit(1);
    }
}

echo "Management panels are present.\n";
