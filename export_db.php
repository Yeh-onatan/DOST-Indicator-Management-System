<?php

// Export database using XAMPP's mysqldump
$socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
$dbname = 'dbDIMS';
$outfile = 'dbDIMS_normalized.sql';
$xamppMysqldump = '/Applications/XAMPP/xamppfiles/bin/mysqldump';

if (!file_exists($xamppMysqldump)) {
    echo "ERROR: XAMPP mysqldump not found at: $xamppMysqldump\n";
    echo "Available paths to try:\n";
    $paths = [
        '/Applications/XAMPP/xamppfiles/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/homebrew/bin/mysqldump',
    ];
    foreach ($paths as $path) {
        echo "  " . ($path) . ": " . (file_exists($path) ? "EXISTS\n" : "NOT FOUND\n");
    }
    exit(1);
}

$cmd = "{$xamppMysqldump} --socket={$socket} -u root {$dbname} > {$outfile} 2>&1";
echo "Running: $cmd\n";

$exitCode = 0;
passthru($cmd, $exitCode);

echo "Exit code: $exitCode\n";

if (file_exists($outfile)) {
    $size = filesize($outfile);
    echo "SUCCESS! File created: $outfile ($size bytes)\n";
    
    // Show first few lines to verify it worked
    echo "\nFirst 5 lines of export:\n";
    $lines = array_slice(file($outfile), 0, 5);
    foreach ($lines as $line) {
        echo "  " . trim($line) . "\n";
    }
} else {
    echo "FAILED: File was not created\n";
}
